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

namespace org\westhoffswelt\revtrans\CryptoLayer\Revelation;

use org\westhoffswelt\revtrans\CryptoLayer\Revelation;
use org\westhoffswelt\revtrans;

/**
 * Implementation of the Revelation crypto layer to read version one files 
 */
class VersionOne extends Revelation 
{
    /**
     * Prepare the key for decrpytion 
     * 
     * @param string $password 
     * @return string
     */
    protected function prepareKey( $password) 
    {
        // This is a really unsecure and evil way of using the password 
        // but Revelation fileversion 1 unfortunately works this way :(
        return str_pad( $password, 32, "\0" );
    }

    /**
     * Check if the given file is a version one revelation file. 
     * 
     * @param string $filename 
     * @return bool
     */
    public static function isApplicable( $filename ) 
    {
        if ( ( $fh = fopen( $filename, "r" ) ) === false ) 
        {
            throw new Exception( "The file '$filename' could not be opened for reading" );
        }

        if ( fread( $fh, 4 ) != chr( 114 ) . chr( 118 ) . chr( 108 ) . chr( 0 ) ) 
        {
            // Wrong magic bytes
            return false;
        }

        if ( ord( fread( $fh, 1 ) ) != 1 ) 
        {
            // Wrong version
            return false;
        }

        return true;
    }
}
