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

namespace org\westhoffswelt\revtrans\StreamWrapper;

use org\westhoffswelt\revtrans;

/**
 * Stream wrapper to allow the reading of encrypted revelation files.
 *
 * This stream wrapper is capable of reading the encrypted Revelation password 
 * file, if the correct passphrase is provided.
 *
 * The stream reader does not store any temporary decrypted data on the disc at 
 * any time. Every decrypted information is kept in memory. However it might be 
 * possible for decrypted data to be written to the hdd, for example due to 
 * swapping done by the os.
 */
class RevelationCrypto 
{
    /**
     * Context which is associated with the opened stream 
     * 
     * @var resource
     */
    public $context;

    /**
     * Fields of the revelation header structure 
     * 
     * @var array
     */
    protected $header = null;

    /**
     * 32 byte padded password to be used for decryption 
     * 
     * @var string
     */
    protected $password = null;

    /**
     * Data once it has been decrypted and decompressed.
     * 
     * @var string
     */
    protected $data = null;

    /**
     * Default constructor called before stream_open is called. 
     * 
     * @return void
     */
    public function __construct() 
    {
        // Nothing to do on construction currenlty.
    }

    public function stream_open( $path, $mode, $options, &$opened_path ) 
    {
        $realpath = realpath( 
            substr( 
                $path,
                strpos( $path, "://" ) + 3
            )
        );

        // Set the realpath if requested
        if ( $options & STREAM_USE_PATH === STREAM_USE_PATH ) 
        {
            $opened_path = $realpath;
        }

        // Check if the mode is acceptable for this file. Currently
        // read-only is supported
        if ( $mode !== "r" ) 
        {
            if ( $options & STREAM_REPORT_ERRORS === STREAM_REPORT_ERRORS ) 
            {
                trigger_error( "Revealtion streams may only be opened for reading", E_USER_ERROR );
            }
            return false;
        }

        // We need a password set in the context options
        if ( $this->context === null
          || !( $contextOptions = stream_context_get_options( $this->context ) )
          || !isset( $contextOptions["revelation"] ) || !isset( $contextOptions["revelation"]["password"] ) ) 
        {
            if ( $options & STREAM_REPORT_ERRORS === STREAM_REPORT_ERRORS ) 
            {
                trigger_error( "The context option containing the password has not been supplied", E_USER_ERROR );
            }
            return false;
        }
        else 
        {
            // This is a really unsecure and evil way of using the password but Revelation
            // unfortunately works this way :(
            $this->password = str_pad( $contextOptions["revelation"]["password"], 32, "\0" );
        }

        if ( !file_exists( $realpath ) || !is_readable( $realpath )
          || ( $fh = fopen( $realpath, "r" ) ) === null ) 
        {
            if ( $options & STREAM_REPORT_ERRORS === STREAM_REPORT_ERRORS ) 
            {
                trigger_error( "The revelation file could not be opened for reading", E_USER_ERROR );
            }
            return false;
        }

        try 
        {
            $this->header = $this->readHeader( $fh );
            $iv = $this->decryptIV( $fh );
            $this->data = \gzuncompress( 
                $this->removePadding( 
                    $this->decryptData( $fh, $iv )
                )
            );
        }
        catch ( \Exception $e )
        {
            if ( $options & STREAM_REPORT_ERRORS === STREAM_REPORT_ERRORS ) 
            {
                trigger_error( $e->getMessage(), E_USER_ERROR );
            }
            return false;
        }

        fclose( $fh );

        return true;
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
     * @param resource $fh 
     * @return array
     */
    protected function readHeader( $fh ) 
    {
        $header = array();
        
        $magic = fread( $fh, 4 );

        if ( $magic != chr( 114 ) . chr( 118 ) . chr( 108 ) . chr( 0 ) ) 
        {
            throw new \Exception( "The given file is not a Revelation password file." );
        }

        $header["magic"] = $magic;

        $header["dataversion"] = ord( fread( $fh, 1 ) );

        fread( $fh, 1 );

        $header["appversion"] = (string)ord( fread( $fh, 1 ) ) . "."
                              . (string)ord( fread( $fh, 1 ) ) . "."
                              . (string)ord( fread( $fh, 1 ) );

        if ( $header["dataversion"] !== 1 ) 
        {
            throw new \Exception( "Incompatible Revelation file version." );
        }

        fread( $fh, 3 );

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
     * @param resource $fh 
     * @return string
     */
    protected function decryptIV( $fh ) 
    {
        return mcrypt_decrypt( 
            MCRYPT_RIJNDAEL_128,
            $this->password,
            fread( $fh, 16 ),
            MCRYPT_MODE_ECB
        );
    }

    /**
     * Decrypt the gzipped XML Data using the stored password and given IV
     *
     * The file pointer has to be set on the first encrypted byte. After the 
     * operation it will be pointing to EOF.
     * 
     * @param mixed $fh 
     * @param mixed $iv 
     * @return string
     */
    protected function decryptData( $fh, $iv ) 
    {
        $decrypted = "";

        $ed = mcrypt_module_open( MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '' );
        mcrypt_generic_init( $ed, $this->password, $iv );

        while( !feof( $fh ) && ( $block = fread( $fh, 16 ) ) !== "" ) 
        {            
            var_dump( ftell( $fh ) );
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
}
