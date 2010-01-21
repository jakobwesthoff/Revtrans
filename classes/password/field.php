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

namespace org\westhoffswelt\revtrans\Password;

use org\westhoffswelt\revtrans;

/**
 * Arbitrary field which is stored inside an Entry.
 *
 * Fields are acutally simple key/value pairs and are only encapsulated inside 
 * of an object, to ease the handling and identification as well as allow for 
 * easy adoption should the Revelation fields evolve. 
 */
class Field 
{
    /**
     * Id which does identify this field 
     * 
     * @var string
     */
    protected $id;

    /**
     * Value associated with this field 
     * 
     * @var string
     */
    protected $value;

    /**
     * Default constructor taking id and value as argument, as both of them are 
     * required. 
     * 
     * @param string $id 
     * @param string $value 
     */
    public function __construct( $id, $value ) 
    {
        $this->id = $id;
        $this->value = $value;
    }

    /**
     * Return the id used to identify this field 
     * 
     * @return string
     */
    public function getId() 
    {
        return $this->id;
    }

    /**
     * Return the value stored in this field 
     * 
     * @return string
     */
    public function getValue() 
    {
        return $this->value;
    }

}
