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
 * Password entry class used to internally store and manage all read 
 * passwords.
 */
class Entry 
{
    /**
     * Folder stack which provides a list of folders this entry resides in.
     *
     * If the entry is not contained in any folders, but does reside inside the 
     * root the array is supposed to be empty
     *
     * @var array
     */
    protected $folders;

    /**
     * Name of this password entry
     *
     * @var string
     */
    protected $name;

    
    /**
     * Type of this entry as it is stored in Revelation 
     * 
     * @var string
     */
    protected $type;

    /**
     * Description of the password entry
     *
     * Descriptions are optional if there is no description stored the value is 
     * null to indicate that.
     *
     * @var string/null
     */
    protected $description = null;

    /**
     * Timestamp at which this entry has been last updated 
     * 
     * @var int
     */
    protected $lastUpdated;
    
    /**
     * Password of the stored entry 
     * 
     * @var string/null
     */
    protected $password = null;

    /**
     * Arbitrary amount of other fields stored as key/value pairs. 
     * 
     * @var array
     */
    protected $fields = array();

    /**
     * Default constructor taking all required information as arguments.
     */
    public function __construct( $folders, $name, $type, $lastUpdated ) 
    {
        $this->folders     = $folders;
        $this->name        = $name;
        $this->type        = $type;
        $this->lastUpdated = $lastUpdated;
    }


    /**
     * Return the name of this password entry 
     * 
     * @return string
     */
    public function getName() 
    {
        return $this->name;
    }

    /**
     * Returns an array of folder names.
     *
     * Each array element does represent a stage in the folder hierachy. 
     * Therefore array( "foo", "bar" ) implies the entry is stored in the 
     * folder "/foo/bar".
     *
     * An entry stored directly beneath the root node is identified having an 
     * empty array as folder stack.
     * 
     * @return array
     */
    public function getFolders() 
    {
        return $this->folders;
    }

    /**
     * Get the type Revelation has originally associated with this entry. 
     * 
     * @return void
     */
    public function getType() 
    {
        return $this->type;
    }

    /**
     * Get the timestamp on which this entry has last been updated in the 
     * password file 
     * 
     * @return int
     */
    public function getLastUpdated() 
    {
        return $this->lastUpdated;
    }

    /**
     * Get an arbitrary field inside the entry identified by its id 
     *
     * In case the id does not match any stored field an Exception will be 
     * thrown.
     * 
     * @param string $id 
     * @return Field
     */
    public function getField( $id ) 
    {
        if( !isset( $this->fields[$id] ) ) 
        {
            throw new \Exception( "The field '$id' is not available in this entry." );
        }

        return $this->fields[$id];
    }

    /**
     * Get all fields of this entry as array
     *
     * The keys of the returned array do match the ids of their according 
     * field objects. 
     * 
     * @return array
     */
    public function getFields() 
    {
        return $this->fields;
    }

    /**
     * Get the description of this entry
     *
     * In case no description has been associated with this entry null is 
     * returned instead. 
     * 
     * @return string/null
     */
    public function getDescription() 
    {
        return $this->description;
    }

    /**
     * Set the description of this entry to the provided string 
     * 
     * @param string $description 
     */
    public function setDescription( $description ) 
    {
        $this->description = $description;        
    }

    /**
     * Add a new Field to this entry 
     * 
     * @param Field $field 
     */
    public function addField( Field $field ) 
    {
        $this->fields[$field->getId()] = $field;
    }

    /**
     * Get the password stored within this entry.
     *
     * Eventhough it does not make a lot of sense entries without passwords are 
     * allowed. Therefore these method may return null in case no password has 
     * been set for this entry. 
     * 
     * @return string/null
    */
    public function getPassword() 
    {
        return $this->password;
    }

    /**
     * Set the password for this entry 
     * 
     * @param string $password 
     */
    public function setPassword( $password ) 
    {
        $this->password = $password;
    }
}
