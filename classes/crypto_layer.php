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

namespace org\westhoffswelt\revtrans;

/**
 * Crypto Layers provide a simple way read certain 
 * encrypted data structures stored in the filesystem.
 *
 * Revelation password files for example are only encrypted and compressed XML 
 * files which are prepended with a special header. 
 *
 * The CryptoLayer provides a generic interface for such files to be opened 
 * and decrypted without the underlying reader having to care about the 
 * encryption. 
 */
abstract class CryptoLayer 
{
    /**
     * File to decrypt 
     * 
     * @var string
     */
    protected $filename;

    /**
     * The password provided for decryption 
     * 
     * @var string
     */
    protected $password;

    /**
     * Construct an EncryptionLayer taking the filename to be read as well as 
     * the password needed as argument. 
     * 
     * @param string $filename 
     * @param string $password 
     */
    public function __construct( $filename, $password ) 
    {
        $this->filename = $filename;
        $this->password = $password;
    }

    /**
     * Decrypt the data file and return the decrypted data 
     * 
     * @return string
     */
    abstract public function decrypt();

    /**
     * Check if the given file can be decrypted by this crypto layer.
     *
     * Because it is sometimes hard to tell if a given file does have a certain 
     * format, the contract for this function is eased a little bit.
     *
     * It may return true even if it is not sure it can really read the file. 
     * If it returns false on the other hand this is intepreted as a guarantee, 
     * that the layer can not interpret the file correctly.
     *
     * Even though these rules apply. The detection should be done as precise 
     * as possible, as it might be used for file auto-detection attempts.
     * 
     * @param string $filename 
     * @return bool
     */
    public static function isApplicable( $filename ) 
    {
        // Unfortunately abstract static methods are not allowed. Therefore 
        // this technique is used.
        throw new Exception( "isApplicable function not implemented. This function needs to be overwritten in the implementing class." );
    }
}
