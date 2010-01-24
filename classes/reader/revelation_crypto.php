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

namespace org\westhoffswelt\revtrans\Reader;

use org\westhoffswelt\revtrans;
use org\westhoffswelt\revtrans\Reader;

/**
 * Reader for the encrypted XML produced by Revelation.
 *
 * This reader is capable of reading the encrypted Revelation password file, if 
 * the correct passphrase is provided.
 *
 * The reader does not store any temporary decrypted data on the disc at any 
 * time. Every decrypted information is kept in memory. However it might be 
 * possible for decrypted data to be written to the hdd, for example due to 
 * swapping done by the os.
 */
class RevelationCrypto extends RevelationXml 
{
    /**
     * Password used to decrypt the file 
     * 
     * @var string
     */
    protected $password;

    /**
     * Construct the reader taking the filename and the required decryption 
     * password as arguments.
     * 
     * @param string $filename 
     * @param string $password 
     */
    public function __construct( $filename, $password ) 
    {
        $this->filename = $filename;
        $this->password = $password;

        $this->initialize();
    }

    /**
     * Initialize the reader by decrypting the password file in memory and 
     * loading its decompressed content into a DOMDocument. 
     */
    protected function initialize() 
    {
        $revelationFile = revtrans\CryptoLayer\Revelation::autodetect( 
            $this->filename, 
            $this->password 
        );

        $errorHandling = \libxml_use_internal_errors( true );
        $this->doc = new \DOMDocument();
        if ( $this->doc->loadXML( $revelationFile->decrypt() ) === false || count( libxml_get_errors() ) !== 0 ) 
        {
            throw new \Exception( "The Revelation XML file seems to be invalid" );
        }
        \libxml_use_internal_errors( $errorHandling );

        // We do not need the decrypted data any longer, as it is now 
        // represented by the DOMDocument. Every piece of it in memory does 
        // provides the risk of it being written to swap.
        unset( $revelationFile );
    }
}
