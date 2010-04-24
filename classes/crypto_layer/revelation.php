<?php
/**
 * Copyright 2010 Jakob Westhoff. All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 
 *    1. Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 * 
 *    2. Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 * 
 * THIS SOFTWARE IS PROVIDED BY JAKOB WESTHOFF ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
 * EVENT SHALL JAKOB WESTHOFF OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
 * OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 * The views and conclusions contained in the software and documentation are those
 * of the authors and should not be interpreted as representing official policies,
 * either expressed or implied, of Jakob Westhoff
**/

namespace org\westhoffswelt\revtrans\CryptoLayer;

use org\westhoffswelt\revtrans\CryptoLayer;
use org\westhoffswelt\revtrans;

/**
 * Crypto Layer to allow the reading of encrypted revelation files.
 *
 * This crypto layer is capable of reading the encrypted Revelation password 
 * file, if the correct passphrase is provided.
 *
 * It is an abstract base interface for implementations of version 1 and 2 of 
 * the revelation file structure, providing a auto-detecting factory.
 *
 * The crypto layer does not store any temporary decrypted data on the disc at
 * any time. Every decrypted information is kept in memory. However it might be 
 * possible for decrypted data to be written to the hdd, for example due to 
 * swapping done by the os.
 */
abstract class Revelation extends CryptoLayer
{
    /**
     * Fields of the revelation header structure 
     * 
     * @var array
     */
    protected $header = null;

    /**
     * 32 byte key to be used for decryption 
     * 
     * @var string
     */
    protected $key = null;

    /**
     * 16 byte initialization vector used for CBC encryption 
     * 
     * @var string
     */
    protected $iv = null;

    /**
     * Data once it has been decrypted and decompressed.
     * 
     * @var string
     */
    protected $data = null;

    /**
     * File handle to the opened revelation file.
     *
     * As different parts of the data are read in subsequently called methods 
     * this class-wide handle storage is needed to not always reopen and seek 
     * the password file. 
     * 
     * @var resource
     */
    protected $fh = null;

    /**
     * Default constructor taking the filepath as well as the needed password 
     * as argument. 
     * 
     * @param string $filename 
     * @param string $password 
     */
    public function __construct( $filename, $password ) 
    {
        parent::__construct( $filename, $password );

        // mcrypt is no default extension therefore we check for its 
        // availability
        if ( !extension_loaded( "mcrypt" ) ) 
        {
            throw new \Exception( "The mcrypt extension is needed to open encrypted Revelation password files." );
        }

        $realpath = realpath( $filename );

        // Make sure the implementation is compatible
        if( !static::isApplicable( $filename ) ) 
        {
            throw new Exception( "The given file does not seem to be a valid Revelation file, or it is of an unknown version." );
        }

        if ( !file_exists( $realpath ) || !is_readable( $realpath )
            || ( $this->fh = fopen( $realpath, "r" ) ) === null ) 
        {
            throw new \Exception( "The revelation file could not be opened for reading" );
        }

        $this->header = $this->readHeader();

        $this->key = $this->prepareKey( $password );

        $this->iv = $this->decryptIV();
    }

    /**
     * Destruct the object and free all bind resources. 
     */
    public function __destruct() 
    {
        fclose( $this->fh );
    }

    /**
     * Return the decrypted and uncompressed revelation xml data
     *
     * Decryption as well as decompressing is done in a lazy way. Therefore the 
     * data will be decrypted and decompressed the first time this method is 
     * called.
     * 
     * @return string
     */
    public function decrypt() 
    {
        if ( $this->data === null ) 
        {
            $decrypted = $this->decryptData();

            if ( !function_exists( 'gzuncompress' ) ) {
                throw new \Exception( "Seems the gzip support of you PHP is not enabled. It needs to be enabled to proceed." );
            }

            // If the data stream is malformed, which happens if a wrong 
            // decryption key is given, than gzuncompress will issue a warning, 
            // which can not be suppressed otherwise. The validity of the gz 
            // stream can't be checked easily, as well. Therefore the error is 
            // silenced.
            $decompressed = @\gzuncompress( $decrypted );

            if ( $decompressed === false ) 
            {
                throw new \Exception( "The Revelation file could not be decrypted/decompressed correctly. Wrong password?" );
            }

            $this->data = $decompressed;
        }

        return $this->data;
    }

    /**
     * Read the header of a revelation file and return a associative array to 
     * represent the read contents.
     *
     * The filehandle pointer is supposed to be positioned right before the 
     * header to be read. After the read is done the pointer has been moved to 
     * the first byte after the header aka the 16 byte IV.
     *
     * The read associative array does have the following structure:
     * <code>
     *  array(
     *      "magic"       => 4 raw bytes,
     *      "dataversion" => int,
     *      "appversion"  => string,
     *  );
     * </code> 
     *
     * Eventhough http://oss.codepoet.no/revelation/wiki/FileFormatSpec 
     * provides a file format specification for Revelation password files these 
     * do not match the real file in any way.
     *
     * The correct header structure is defined as follows:
     *
     *   "rvl" 0x00      # magic string
     *   1 byte          # data version
     *   0x00            # separator
     *   3 byte          # application version
     *   0x00 0x00 0x00  # separator
     * 
     * @return array
     */
    protected function readHeader() 
    {
        $header = array();
        
        $magic = fread( $this->fh, 4 );

        if ( $magic != chr( 114 ) . chr( 118 ) . chr( 108 ) . chr( 0 ) ) 
        {
            throw new \Exception( "The given file is not a Revelation password file." );
        }

        $header["magic"] = $magic;

        $header["dataversion"] = ord( fread( $this->fh, 1 ) );

        fread( $this->fh, 1 );

        $header["appversion"] = (string)ord( fread( $this->fh, 1 ) ) . "."
                              . (string)ord( fread( $this->fh, 1 ) ) . "."
                              . (string)ord( fread( $this->fh, 1 ) );

        if ( $header["dataversion"] !== 1 && $header["dataversion"] !== 2 ) 
        {
            throw new \Exception( "Incompatible Revelation file version." );
        }

        fread( $this->fh, 3 );

        return $header;
    }

    /**
     * Read and decrypt the 16 byte IV
     *
     * The file pointer is supposed to be set to the first byte of the IV.  
     * After the method finished the pointer will be positioned on the first 
     * byte after the IV. 
     *
     * The IV is encrypted directly after the header using the password and the 
     * "Electronic Codebook" block cipher.
     * 
     * @return string
     */
    protected function decryptIV() 
    {
        return mcrypt_decrypt( 
            MCRYPT_RIJNDAEL_128,
            $this->key,
            fread( $this->fh, 16 ),
            MCRYPT_MODE_ECB
        );
    }

    /**
     * Decrypt the gzipped XML Data using the stored password and given IV
     *
     * The file pointer has to be set on the first encrypted byte. After the 
     * operation it will be pointing to EOF.
     * 
     * @return string
     */
    protected function decryptData() 
    {
        $decrypted = "";

        $ed = mcrypt_module_open( MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '' );
        mcrypt_generic_init( $ed, $this->key, $this->iv );

        while( !feof( $this->fh ) && ( $block = fread( $this->fh, 16 ) ) !== "" ) 
        {            
            $decrypted .= mdecrypt_generic( 
                $ed, 
                $block
            );
        }

        mcrypt_generic_deinit( $ed );
        mcrypt_module_close( $ed );

        return $decrypted;
    }

    /**
     * Remove the padding from a given data string
     *
     * The last char defines the number of characters which have been used for 
     * padding. Furthermore every padding character needs to be this character. 
     * 
     * @param string $data 
     * @return string
     */
    protected function removePadding( $data ) 
    {
        $padding = ord( $paddingCharacter = substr( $data, -1 ) );
        return substr( $data, 0, $padding * -1 );
    }

    /**
     * Prepare the decryption key. If extra work for this like hashing or 
     * reading a salt is needed for this, it is assumed to be done here. 
     *
     * The outputted key has to be the one used directly for the decryption 
     * sequence.
     * 
     * @param string $password 
     * @return string
     */
    abstract protected function prepareKey( $password );

    /**
     * Factory method to autodetect the version of a revelation file and return 
     * the correct crypto layer. 
     * 
     * @param string $filename 
     * @param string $password 
     * @return Revelation
     */
    public static function autodetect( $filename, $password ) 
    {
        foreach( array( "VersionOne", "VersionTwo" ) as $implementation ) 
        {
            $layer = "\\org\\westhoffswelt\\revtrans\\CryptoLayer\\Revelation\\$implementation";
            if ( $layer::isApplicable( $filename ) ) 
            {
                return new $layer( $filename, $password );
            }
        }

        throw new Exception( "No Revelation file handler could be autodetected for file '$filename'" );
    }
}
