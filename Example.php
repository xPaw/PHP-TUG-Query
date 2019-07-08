<?php
	require __DIR__ . '/TUGQuery.class.php';
	
	// For the sake of this example
	Header( 'Content-Type: text/plain' );
	
	// Edit this ->
	define( 'SERVER_ADDR', 'localhost' );
	define( 'SERVER_PORT', 6601 );
	define( 'TIMEOUT', 2 );
	// Edit this <-
	
	$Query = new TUGQuery( );
	
	try
	{
		// GetInfo can be called for multiple servers
		print_r( $Query->GetInfo( SERVER_ADDR, SERVER_PORT, TIMEOUT ) );
	}
	catch( TUGQueryException $e )
	{
		echo $e->getMessage( );
	}
