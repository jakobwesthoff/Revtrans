#!/usr/bin/env php
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

include( "classes/reader.php" );
include( "classes/password/field.php" );
include( "classes/password/entry.php" );
include( "classes/password/entry_list.php" );
include( "classes/reader/revelation_xml.php" );
include( "classes/reader/data_provider/revelation_crypto.php" );
include( "classes/reader/revelation_crypto.php" );
include( "classes/writer.php" );
include( "classes/writer/secrets_csv/entry.php" );
include( "classes/writer/secrets_csv.php" );

echo "RevTrans - Revelation Password File Transformer\n";
echo "Copyright 2010 Jakob Westhoff\n";
echo "\n";


$options = getopt( 
    "",
    array(
        "input-format:",
        "password:",
        "output:",
    )
);

if ( $options === false ) 
{
    fwrite( STDERR, "Could not parse options correctly.\n" );
    exit( 2 );
}

// Remove the options from the argv array
array_splice( $argv, 1, count( $options ) );


if ( $argc < 2 ) 
{
    $basename = basename( $argv[0] );
    echo <<<USAGE
Usage: 
  $basename [OPTIONS] <input file>


Options:
  --input-format=<plain,encrypted>  Input format to read (Default: encrypted)
  --password=<password>             Password to use for decryption. It is 
                                    discouraged to supply a password on the 
                                    commandline. You will be asked for one if
                                    neccessary.
  --output=<file>                   Write output to a file instead of stdout.

USAGE;

    exit ( 1 );
}

// Set default options for the different parameters
if ( !isset( $options['input-format'] ) )
{
    $options['input-format'] = "encrypted";
}
if ( !isset( $options['output'] ) )
{
    $options['output'] = "php://stdout";
}

// Maybe a password input is needed
if ( $options['input-format'] == "encrypted" && ( !isset( $options["password"] ) || $options["password"] === "" ) ) 
{
    // Read password without printing it
    echo "Password: ";
    system( 'stty -echo' );
    $options["password"] = trim( fgets( STDIN ) );
    system( 'stty echo' );
    // The disabled echo sucked up the newline. Therefore we need to output it 
    // manually here
    echo "\n";
}

try 
{
    switch( $options["input-format"] ) 
    {
        case "plain":
            $reader = new Reader\RevelationXml( $argv[1] );
        break;
        case "encrypted":
            $reader = new Reader\RevelationCrypto( $argv[1], $options["password"] );
        break;
    }

    $passwords = $reader->load();
    $writer = new Writer\SecrectsCsv( $passwords );
    $writer->save( $options["output"] );
}
catch( \Exception $e ) 
{
    fwrite( STDERR, $e->getMessage() . "\n" );
    exit( 3 );
}
