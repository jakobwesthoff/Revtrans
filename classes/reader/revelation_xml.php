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
 * Reader for the plain XML produced by Revelation.
 *
 * This reader is only capable of reading the decrypted XML file which can be 
 * created using Revelations export feature.
 */
class RevelationXml extends Reader 
{
    /**
     * Mapping of entry type attributes to special visitor functions for this 
     * type of entry
     */
    protected $entryMapping = array( 
        "folder"    =>  "visitFolder",
    ); 

    /**
     * Mapping of field id attributes to special visitor functions for this 
     * type of field
     */
    protected $fieldMapping = array(
        "generic-password"  => "visitPassword",  
    ); 

    /**
     * DOMDocument used to process the provided password file
     *
     * @var \DOMDocument
     */
    protected $doc = null;

    /**
     * Folder stack which holds the current chain of folders the visitor has 
     * descended into.
     * 
     * @var array
     */
    protected $folders = array();

    /**
     * List filled up with all the read password entries
     *
     * @var Password\EntryList
     */
    protected $passwordList = null;

    /**
     * The password entry currently visited and processed
     *
     * @var Password\Entry
     */
    protected $currentEntry = null;

    /**
     * Initialize the DOMDocument used to process the provided password file. 
     */
    protected function initialize() 
    {
        if ( !file_exists( $this->filename )
          || !is_readable( $this->filename ) ) 
        {
            throw new \Exception( "The input file '{$this->filename}' could not be opened for reading." );
        } 

        $errorHandling = \libxml_use_internal_errors( true );
        $this->doc = new \DOMDocument();
        if ( $this->doc->load( $this->filename ) === false || count( libxml_get_errors() ) !== 0 ) 
        {
            throw new \Exception( "The Revelation XML file seems to be invalid" );
        }
        \libxml_use_internal_errors( $errorHandling );
    }

    /**
     * Commence the visition of the XML file returning the parsed data 
     * structure as a result. 
     *
     * @return revtrans\Password\EntryList
     */
    public function load()   
    {
        // Reset all temporary information which may still be present from the 
        // last visiting cycle.
        $this->folders      = array();
        $this->passwordList = new revtrans\Password\EntryList();
        $this->currentEntry = null;
        
        // Start by visiting all "entry" elements of the first level. All 
        // further visiting is recursive.
        $xpath   = new \DOMXPath( $this->doc );
        $entries = $xpath->query( "/revelationdata/entry" );
        foreach( $entries as $entry ) 
        {
            $this->visitEntry( $entry );
        }

        return $this->passwordList;
    } 

    /**
     * Vist the DOMNode "entry"
     */
    protected function visitEntry( \DOMNode $entry ) 
    {
        // Entries are devided by their "type" attribute.
        $type = $entry->attributes->getNamedItem( "type" );
        if ( $type === null ) 
        {
            // Untyped entries are not allowed according to Revelation file 
            // definition
            throw new \Exception( "Untyped entry detected in: /" . implode( "/", $this->folders ) );
        }

        if ( !isset( $this->entryMapping[(string)$type->nodeValue] ) ) 
        {
            // Use the default entry visitor function, as no mapping for the 
            // defined type is defined.
            $this->visitEntryDefault( (string)$type->nodeValue, $entry );
        }
        else 
        {
            $this->{$this->entryMapping[(string)$type->nodeValue]}( $entry );
        }
    }

    /**
     * Visit an entry element which is of the type folder
     *
     * This method simple extracts the folder name, adds it to the folder stack 
     * and recurses deeper into the tree
     */
    protected function visitFolder( \DOMNode $entry ) 
    {
        // Fetch the folder name, which is a required field in the XML file
        $xpath  = new \DOMXPath( $this->doc );
        $result = $xpath->query( "./name", $entry );
        if ( $result->length === 0 ) 
        {
            // Folders without a name are invalid according to the Revelation 
            // XML file specification.
            throw new \Exception( "Folder with undefined name detected, residing in: /" . implode( "/", $this->folders ) );
        }
        array_push( $this->folders, (string)$result->item( 0 )->nodeValue );

        // Descend deeper into the tree
        $entries = $xpath->query( "./entry", $entry );
        foreach( $entries as $entry ) 
        {
            $this->visitEntry( $entry );
        }

        // We left the folder, therefore remove it from the stack
        array_pop( $this->folders );
    }

    /**
     * Default visitor for an arbitrary entry node, which does not have any 
     * special handler
     *
     * @param mixed $type
     * @param \DOMNode $entry
     */
    protected function visitEntryDefault( $type, \DOMNode $entry ) 
    {
        // Name and updated elements are required child elements.
        $xpath         = new \DOMXPath( $this->doc );
        $name    = $xpath->query( "./name", $entry );
        $updated = $xpath->query( "./updated", $entry );

        if ( $name->length === 0 ) 
        {
            throw new \Exception( "Entry without name detected in: /" . implode( "/", $this->folders ) );
        }

        if ( $updated->length === 0 ) 
        {
            throw new \Exception( 
                "Entry without update timestamp detected: /" 
                . implode( "/", $this->folders ) 
                . ( count( $this->folders ) === 0  ? "" : "/" ) 
                . (string)$name->item( 0 )->nodeValue 
            );
        }

        // Create a new Password\Entry for the visited entry
        $this->currentEntry = new revtrans\Password\Entry( 
            $this->folders,
            (string)$name->item( 0 )->nodeValue,
            $type,
            (int)$updated->item( 0 )->nodeValue
        );

        // There may be a description available. However it is optional.
        $description = $xpath->query( "./description", $entry );
        if ( $description->length >= 1 ) 
        {
            $this->currentEntry->setDescription( (string)$description->item( 0 )->nodeValue );
        }

        // Each entry does have an arbitrary amount of fields, which need to be 
        // handled correctly.
        $fields = $xpath->query( "./field", $entry );
        foreach( $fields as $field ) 
        {
            $this->visitField( $field );
        }

        // Add the created entry to the Password\EntryList to finish its processing
        $this->passwordList->addEntry( $this->currentEntry );

        // The currently processed entry is not needed any longer
        $this->currentEntry = null;
    }

    /**
     * Visit an arbitrary field inside an entry node
     *
     * This method takes care of dispatching to the correct visitor method 
     * according to the id of the field.
     */
    protected function visitField( \DOMNode $field ) 
    {
        // Fields are devided by their "id" attribute.
        $id = $field->attributes->getNamedItem( "id" );
        if ( $id === null ) 
        {
            // Untyped fields are not allowed according to Revelation file 
            // definition
            throw new \Exception( 
                "Untyped field detected in: /"
                . implode( "/", $this->folders ) 
                . ( count( $this->folders ) === 0  ? "" : "/" ) 
                . $this->currentEntry->getName() 
            );
        }
        if ( !isset( $this->fieldMapping[(string)$id->nodeValue] ) ) 
        {
            // Use the default fields visitor function, as no id is set or no 
            // mapping for the found id is defined.
            $this->visitFieldDefault( (string)$id->nodeValue, $field );
        }
        else 
        {
            $this->{$this->fieldMapping[(string)$id->nodeValue]}( $field );
        }
    }

    /**
     * Vist a field which does not provide any special handler 
     * 
     * @param mixed $id 
     * @param \DOMNode $field 
     */
    protected function visitFieldDefault( $id, \DOMNode $field ) 
    {
        $this->currentEntry->addField( 
            new revtrans\Password\Field( $id, (string)$field->nodeValue )
        );
    }

    /**
     * Visit a "generic-password" field 
     * 
     * As this is stored as a special attribute in the Password\Entry it needs 
     * a special handling.
     * 
     * @param \DOMNode $field 
     */
    protected function visitPassword( \DOMNode $field ) 
    {
        $this->currentEntry->setPassword( (string)$field->nodeValue );
    }
}
