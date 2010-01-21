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
 * Abstract interface class which every input reader needs to implement.
 */
abstract class Reader 
{
    /**
     * Filename which is processed as input 
     * 
     * @var string
     */
    protected $filename;

    /**
     * Default constructor taking filename to be processed as argument 
     * 
     * @param string $filename 
     */
    public function __construct( $filename ) 
    {
        $this->filename = $filename;
        $this->initialize();
    }

    /**
     * Initialize the reader before any further action is called upon him. 
     *
     * The default implementation of this method is empty. It allows 
     * implementing classes to easily intervene during the initialization 
     * process of the reader to do their own stuff.
     */
    protected function initialize() 
    {
        // Nothing done in base class
    }

    /**
     * Load the file and transform it into a Password\List data structure which 
     * has to be returned.
     * 
     * @return Password\List
     */
    abstract public function load();
}
