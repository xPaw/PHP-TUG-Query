<?php
class TUGQueryException extends Exception
{
	//
}

class TUGQuery
{
	/*
	 * Class written by xPaw
	 *
	 * Website: https://xpaw.me
	 * GitHub: https://github.com/xPaw
	 */
	
	private $Socket;
	
	public function GetInfo( $Ip, $Port, $Timeout = 3 )
	{
		if( !is_int( $Timeout ) || $Timeout < 0 )
		{
			throw new InvalidArgumentException( 'Timeout must be an integer.' );
		}
		
		$this->Socket = FSockOpen( 'tcp://' . $Ip, (int)$Port, $ErrNo, $ErrStr, $Timeout );
		
		if( $ErrNo || $this->Socket === false )
		{
			throw new TUGQueryException( 'Could not create socket: ' . $ErrStr );
		}
		
		Stream_Set_Timeout( $this->Socket, $Timeout );
		Stream_Set_Blocking( $this->Socket, true );
		
		if( !$this->WriteData( ) )
		{
			FClose( $this->Socket );
			
			$this->Socket = null;
			
			throw new TUGQueryException( 'Failed to write to socket.' );
		}
		
		$Info = $this->ReadData( );
		
		FClose( $this->Socket );
		
		$this->Socket = null;
		
		if( $Info === false )
		{
			throw new TUGQueryException( 'Failed to read from socket.' );
		}
		
		return $Info;
	}
	
	private function WriteData( )
	{
		$Requests = [ 0, 1, 2, 5, 6, 4 ];
		
		$Command = pack( 'n*',
			1, // Query interface version
			0, // Query packet ID (0 for info request)
			2 * count( $Requests ), // Length of packet's body (should be 2 * #)
			0 // Request ID (the server will echo this number on a response)
		);
		
		foreach( $Requests as $Request )
		{
			$Command .= pack( 'n', $Request );
		}
		
		$Length = StrLen( $Command );
		
		return $Length === FWrite( $this->Socket, $Command, $Length );
	}
	
	private function ReadData( )
	{
		$Data = FRead( $this->Socket, 1024 );
		
		if( $Data === false )
		{
			return false;
		}
		
		$Info = [];
		
		while( strlen( $Data ) >= 8 )
		{
			$ID = unpack( 'n', substr( $Data, 2, 2 ) );
			$Length = unpack( 'n', substr( $Data, 4, 2 ) );
			
			// Slice off header
			$Data = substr( $Data, 8 );
			
			switch( $ID[ 1 ] )
			{
				case 0: // Server Engine Version
				{
					$Major = unpack( 'n', substr( $Data, 0, 2 ) );
					$Minor = unpack( 'n', substr( $Data, 2, 2 ) );
					$Patch = unpack( 'n', substr( $Data, 4, 2 ) );
					
					$Info[ 'Version' ] = $Major[ 1 ] . '.' . $Minor[ 1 ] . '.' . $Patch[ 1 ];
					
					break;
				}
				case 1: // Server Name
				{
					$StringLength = unpack( 'n', substr( $Data, 0, 2 ) );
					
					$Info[ 'Name' ] = substr( $Data, 2, $StringLength[ 1 ] - 1 );
					
					break;
				}
				case 2: // Server Description
				{
					$StringLength = unpack( 'n', substr( $Data, 0, 2 ) );
					
					$Info[ 'Description' ] = substr( $Data, 2, $StringLength[ 1 ] - 1 );
					
					break;
				}
				case 3: // Player Count
				{
					$Players = unpack( 'N', substr( $Data, 0, 4 ) );
					$MaxPlayers = unpack( 'N', substr( $Data, 4, 4 ) );
					
					$Info[ 'Players' ] = $Players[ 1 ];
					$Info[ 'MaxPlayers' ] = $MaxPlayers[ 1 ];
					
					break;
				}
				case 4: // Player Count & Player List
				{
					$Players = unpack( 'N', substr( $Data, 0, 4 ) );
					$MaxPlayers = unpack( 'N', substr( $Data, 4, 4 ) );
					
					$Info[ 'Players' ] = $Players[ 1 ];
					$Info[ 'MaxPlayers' ] = $MaxPlayers[ 1 ];
					
					$Index = 8;
					$Info[ 'PlayersOnServer' ] = [];
					
					for( $i = 0; $i < $Players[ 1 ]; $i++ )
					{
						$StringLength = unpack( 'n', substr( $Data, $Index, 2 ) );
						
						$Index += 2;
						
						$Info[ 'PlayersOnServer' ][] = substr( $Data, $Index, $StringLength[ 1 ] - 1 );
						
						$Index += $StringLength;
					}
					
					break;
				}
				case 5: // World Name
				{
					$StringLength = unpack( 'n', substr( $Data, 0, 2 ) );
					
					$Info[ 'WorldName' ] = substr( $Data, 2, $StringLength[ 1 ] - 1 );
					
					break;
				}
				case 6: // Game Mode
				{
					$StringLength = unpack( 'n', substr( $Data, 0, 2 ) );
					
					$Info[ 'GameMode' ] = substr( $Data, 2, $StringLength[ 1 ] - 1 );
					
					break;
				}
			}
			
			$Data = substr( $Data, $Length[ 1 ] );
		}
		
		return $Info;
	}
}
