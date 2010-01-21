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

namespace org\westhoffswelt\revtrans\Writer\SecretsCsv;

/**
 * SecretsCsv\Entry storing all the needed information for one csv entry
 *
 * Furthermore this class takes care of proper formatting and escaping of the 
 * provided column data. 
 */
class Entry 
{
    /**
     * Description of this entry
     *
     * This is the first column 
     * 
     * @var string
     */
    protected $description;

    /**
     * Id of this entry
     *
     * This is the second column 
     * 
     * @var string
     */
    protected $id;

    /**
     * Pin of this entry aka password of this entry
     *
     * This is the third column 
     * 
     * @var string
     */
    protected $pin;

    /**
     * EMail address associated with this entry
     *
     * This is the fourth column 
     * 
     * @var string
     */
    protected $email;

    /**
     * Notes associated with this entry
     * 
     * This is the fifth column.
     *
     * Notes may contain multiline information. 
     */
    protected $notes;

    /**
     * Construct a new entry
     *
     * Each of the columns may be specified as argument during construction. If 
     * null is specified the column is supposed to be empty. It may however be 
     * set later on using the appropriate setter methods. 
     * 
     * @param string $description 
     * @param string $id 
     * @param string $pin 
     * @param string $email 
     * @param string $notes 
     */
    public function __construct( $description = null, $id = null, $pin = null, $email = null, $notes = null ) 
    {
        $this->description = $description === null ? "" : $description;
        $this->id          = $id === null ? "" : $id;
        $this->pin         = $pin === null ? "" : $pin;
        $this->email       = $email === null ? "" : $email;
        $this->notes       = $notes === null ? "" : $notes;
    }

    /**
     * Get the stored description 
     * 
     * @return string
     */
    public function getDescription() 
    {
        return $this->description;
    }

    /**
     * Set the description column for this entry
     *
     * Escaping of special characters will be done automatically. 
     * 
     * @param string $description 
     */
    public function setDescription( $description ) 
    {
        $this->description = $description;
    }

    /**
     * Get the stored id 
     * 
     * @return string
     */
    public function getId() 
    {
        return $this->id;
    }

    /**
     * Set the id column for this entry
     *
     * Escaping of special characters will be done automatically. 
     * 
     * @param string $id 
     */
    public function setId( $id ) 
    {
        $this->id = $id;
    }

    /**
     * Get the stored pin 
     * 
     * @return string
     */
    public function getPin() 
    {
        return $this->pin;
    }

    /**
     * Set the pin column for this entry
     *
     * Escaping of special characters will be done automatically. 
     * 
     * @param string $pin 
     */
    public function setPin( $pin ) 
    {
        $this->pin = $pin;
    }

    /**
     * Get the stored email 
     * 
     * @return string
     */
    public function getEMail() 
    {
        return $this->email;
    }

    /**
     * Set the email column for this entry
     *
     * Escaping of special characters will be done automatically. 
     * 
     * @param string $email 
     */
    public function setEMail( $email ) 
    {
        $this->email = $email;
    }

    /**
     * Get the stored notes 
     * 
     * @return string
     */
    public function getNotes() 
    {
        return $this->notes;
    }

    /**
     * Set the notes column for this entry
     *
     * Escaping of special characters will be done automatically. 
     * 
     * @param string $notes 
     */
    public function setNotes( $notes ) 
    {
        $this->notes = $notes;
    }

    /**
     * Append a new note to the notes field.
     *
     * The new note will automatically start on a newline, as well as end with 
     * a newline character. 
     * 
     * @param string $note 
     */
    public function addNote( $note ) 
    {
        if ( !substr( $this->notes, -1 ) === "\n" ) 
        {
            $this->notes .= "\n";
        }

        $this->notes .= $note;

        $this->notes .= "\n";
    }

    /**
     * Convert the entry into a csv based string
     *
     * This is the default way of retrieving the entry for output. The 
     * conversion will take care of every needed escaping to conform to the csv 
     * files read by Secrets.
     *
     * The outputted string is guaranteed to end with a newline. 
     * 
     * @return string
     */
    public function __tostring() 
    {
        $csv = $this->escapeColumn( $this->description ) . ","
             . $this->escapeColumn( $this->id ) . ","
             . $this->escapeColumn( $this->pin ) . ","
             . $this->escapeColumn( $this->email ) . ","
             . $this->escapeColumn( $this->notes ) . "\n";

        return $csv;
    }

    /**
     * Escape a given string to correct column format which can than be read by 
     * the Secrets CSV importer. 
     * 
     * @param string $column 
     * @return string
     */
    protected function escapeColumn( $column ) 
    {
        // If there is no column content it is not enclosed in quotes, because 
        // the parser does not handle this properly
        if ( strlen( $column ) === 0 ) 
        {
            return "";
        }
        else 
        {
            // The only escaping needed I am currently aware of is the 
            // encapsulation in double quotes, as well as to escape every in string 
            // double quote with two double quote characters following directly 
            // each other.

            return '"' . str_replace( '"', '""', $column ) . '"';
        }

    }
}
