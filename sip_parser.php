<?
/**************************************************************
Copyright (c) 2009-present, Victor Villarreal <mefhigoseth@gmail.com>
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
    * Redistributions of source code must retain the above copyright
      notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
      notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of the EuropeSIP nor the
      names of its contributors may be used to endorse or promote products
      derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY Victor Villarreal ''AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL Victor Villarreal BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

File:			sip_parser.php
Version:		v1.0
Date:			21-01-2009
Brief:			Asterisk config PHP parser library.

*************************************************/

function sip_clear_line( $string )
{
      // Quito los espacios y dem�s caracteres raros de la linea...
      $clear01 = trim($string);
      // Elimino los comentarios...
      $clear02 = explode( ";",$clear01 );
      return $clear02[0];
}

function wop_parseSIP( $file )
{ //v1.5 [18-08-2009]
      // Variables must be static for them to last in time, because of function being recursive
      static $sipTemplates = array();
      static $users = array();
      static $ftpl = 0;
      static $sip_line = "";
      // Parse sip.conf
      $fp = @fopen($file, "r");
      if ($fp)
      {
          static $sip_config = array();
          while (!feof($fp))
          {
              $sip_config_line = fgets($fp, 1024);
              $sip_line = sip_clear_line($sip_config_line);
              // If string is empty, do nothing. It must be either blank or a commentary
              if( !empty($sip_line) )
              {
                  if( strpos($sip_line, "](")!==FALSE )
                  {
                        $sip_param = explode( "](",$sip_line );
                        $sip_param[0] = trim($sip_param[0]);
                        $sip_param[0] = substr( $sip_param[0],1 );
                        $sip_param[1] = trim($sip_param[1]);
                        $sip_param[1] = substr( $sip_param[1],0,-1 );
                        if( $sip_param[1]=="!" )
                        {
                              $ftpl = 1;
                              $usr = $sip_param[0];
                              $sipTemplates[] = (string)$usr;
                        }
                        else
                        {
                              $ftpl = 0;
                              $usr = $sip_param[0];
                              $users[(string)$usr]['type'] = "friend";
                              $users[$usr] = array_merge( $users[$usr],$sipTemplates[$sip_param[1]] );
                        }
                  }
                  else
                  {
                        if( strpos($sip_line, "[")!==FALSE )
                        {
                              $sip_line = substr( $sip_line,1,-1 );
                              $ftpl = 0;
                              $usr = $sip_line;
                              //$users[] = $usr;
                              //$users[(string)$usr]['name'] = (string)$usr;
                              $users[(string)$usr]['type'] = "friend";
                        }
                        else
                        {
                              if( strpos($sip_line, "=>")!==FALSE )
                                    $sip_param = explode( "=>",$sip_line );
                              elseif( strpos($sip_line, "#")!==FALSE )
                                    $sip_param = explode( " ",$sip_line );
                              else
                                    $sip_param = explode( "=",$sip_line );
                              // $sip_param[0] = Key...
                              $sip_param[0] = trim($sip_param[0]);
				$sip_param[0] = strtolower($sip_param[0]);
                              // $sip_param[1] = Value...
                              $sip_param[1] = trim($sip_param[1]);

                              if( $sip_param[0]=="#include" )
                              {
                                    if( $includedFile = wop_parseSIP(dirname($file)."/".$sip_param[1]) )
                                    {
                                          foreach( $includedFile as $key => $value )
                                                $users[$key] = array_merge( $includedFile[$key] );
                                    }
                              }
                              else
                              {
                                    if($ftpl)
                                    {
                                          // Concatenate parameter if already exists for this user
                                          if( isset($sipTemplates[$usr]) && array_key_exists($sip_param[0],$sipTemplates[$usr]) )
                                                $sipTemplates[(string)$usr][(string)$sip_param[0]] .= ";".$sip_param[1];
                                          else
                                                $sipTemplates[(string)$usr][(string)$sip_param[0]] = (string)$sip_param[1];
                                    }
                                    else
                                    {
                                          // Concatenate parameter if already exists for this user
                                          if( isset($users[$usr]) && array_key_exists( (string)$sip_param[0], $users[$usr]) && $sip_param[0]=="allow" )
                                                $users[(string)$usr][(string)$sip_param[0]] .= ";".$sip_param[1];
                                          else
                                                $users[(string)$usr][(string)$sip_param[0]] = (string)$sip_param[1];
                                    }
                              }
                        }
                  }
              }
          }
          fclose($fp);
          return $users;
      }
      else
            return FALSE;
}

?>
