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

namespace org\westhoffswelt\revtrans\Writer;

use org\westhoffswelt\revtrans;
use org\westhoffswelt\revtrans\Writer;

/**
 * Writer for the CSV based import format of the Secrects for Android 
 * application.
 *
 * Eventhough the secrects import format is quite limited the writer tries to 
 * map all available information to useful fields to be displayed correctly by 
 * secrets.
 */
class SecrectsCsv extends Writer 
{
    /**
     * Mapping of field names to special visitor functions
     *
     * Every field not specifically mapped here will be visited using a default 
     * visitor implementation, which does just write out the field to the notes 
     * section. 
     * 
     * @var array
     */
    protected $fieldMapping = array();

    /**
     * Handle to the opened targetfile. 
     * 
     * @var resource
     */
    protected $target = null;

    /**
     * Currently processed entry object
     * 
     * @var SecrectsCsv\Entry
     */
    protected $currentEntry = null;

    /**
     * Save the provided data structure to the output csv file, trying to map 
     * all the available fields to useful counterparts in the secrets 
     * application. 
     * 
     * @param string $filename 
     */
    public function save( $filename ) 
    {
        // Reset all temporary storage variable which might be still contain 
        // data from the last processing
        $this->currentEntry = null;

        // Overwriting existing files is evil in most situations
        if ( file_exists( $filename ) ) 
        {
            throw new \Exception( "The file '$filename' does already exist." );
        }

        // Open the file and do error checking in one step
        if ( ( $this->target = fopen( $filename, "w+" ) ) === false ) 
        {
            throw new \Exception( "The file '$filename' can't be opened for writing." );
        }

        // Create the first line of the csv file, which has to consist of the 
        // column names.
        fwrite( 
            $this->target,
            (string)new SecretsCsv\Entry( "Description", "Id", "PIN", "Email", "Notes" )
        );

        foreach( $this->passwordList->getEntries() as $entry ) 
        {
            $this->visitEntry( $entry );
        }

        fclose( $this->target );
    }

    /**
     * Visit each entry and process it 
     * 
     * @param revtrans\Password\Entry $entry 
     */
    protected function visitEntry( revtrans\Password\Entry $entry ) 
    {
        // Create a new entry object holding all the information while the 
        // visiting takes places
        $this->currentEntry = new SecretsCsv\Entry();

        $this->visitType( $entry->getType() );
        $this->visitFolders( $entry->getFolders() );
        $this->visitName( $entry->getName() );
        $this->visitDescription( $entry->getDescription() );
        $this->visitPassword( $entry->getPassword() );
        $this->visitLastUpdated( $entry->getLastUpdated() );
        $this->visitFields( $entry->getFields() );

        // Write out the current entry
        fwrite( $this->target, (string)$this->currentEntry );
    }

    /**
     * Visit the type information stored in each entry 
     * 
     * @param string $type 
     */
    protected function visitType( $type ) 
    {
        $this->currentEntry->addNote( "Revelation-Type: $type" );
    }

    /**
     * Vist the folder stack of the processed entry 
     * 
     * @param array $folders 
     */
    protected function visitFolders( $folders ) 
    {
        // Folders are prepended to the name of the entry to ensure unique 
        // names as well as easily searchable entries.
        if ( count( $folders ) === 0 ) 
        {
            // The entry resides in the root.
            $this->currentEntry->setDescription( "/" );
            return;
        }

        $this->currentEntry->setDescription( 
            "/" .
            implode( "/", $folders ) .
            "/"
        );
    }

    /**
     * Visit the name of the processed entry 
     * 
     * @param string $name 
     */
    public function visitName( $name ) 
    {
        // There might be a folder already stored in the description therefore 
        // we update it instead of replacing it.
        $this->currentEntry->setDescription( 
            $this->currentEntry->getDescription() .
            $name
        );
    }

    /**
     * Visit the description 
     * 
     * @param string $description 
     */
    public function visitDescription( $description ) 
    {
        $this->currentEntry->addNote( "Description: $description" );
    }

    /**
     * Visit the associated password 
     * 
     * @param string $password 
     */
    public function visitPassword( $password ) 
    {
        $this->currentEntry->setPin( $password );
    }

    /**
     * Visit the last updated timestamp 
     * 
     * @param int $lastUpdated 
     */
    public function visitLastUpdated( $lastUpdated ) 
    {
        $this->currentEntry->addNote( "Last updated: $lastUpdated" );
    }

    /**
     * Visit the fields substructure 
     * 
     * @param array $fields 
     */
    public function visitFields( $fields ) 
    {
        foreach( $fields as $id => $field ) 
        {
            if ( isset( $this->fieldMapping[$id] ) ) 
            {
                $this->{$this->fieldMapping[$id]}( $field );
            }
            else 
            {
                $this->visitFieldDefault( $id, $field );
            }
        }
    }

    /**
     * Default field visitor in case no special one can be found for the 
     * currently processed field 
     * 
     * @param string $id 
     * @param revtrans\Password\Field $field 
     */
    public function visitFieldDefault( $id, $field ) 
    {
        $this->currentEntry->addNote( "{$id}: " . $field->getValue() );
    }
} 
