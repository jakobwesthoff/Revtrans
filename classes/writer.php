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
 * Abstract interface class which every output writer needs to implement.
 */
abstract class Writer 
{
    /**
     * Password\EntryList provided at object instantiation to be used as a data 
     * source for writing. 
     */
    protected $passwordList;

    /**
     * Default constructor taking the source to be used for writing as required 
     * argument.
     * 
     * @param Password\EntryList $passwordList 
     */
    public function __construct( Password\EntryList $passwordList ) 
    {
        $this->passwordList = $passwordList;
        $this->initialize();
    }

    /**
     * Initialize the writer before any further action is called upon him. 
     *
     * The default implementation of this method is empty. It allows 
     * implementing classes to easily intervene during the initialization 
     * process of the writer to do their own stuff.
     */
    protected function initialize() 
    {
        // Nothing done in base class
    }

    /**
     * Save the initially provided data structure to the given filename using 
     * the fileformat defined by the implementing class. 
     * 
     * @param string $filename 
     */
    abstract public function save( $filename );
}
