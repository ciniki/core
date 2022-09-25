<?php
//
// Description
// -----------
// This code was originally in prepareArgs, but was moved so it could be called multiple times in bulk add API calls.
// 
// Arguments
// ---------
// ciniki:          The ciniki variable.
// args:            The argument array to parse.
// arg_info:        The array of arguments to be parsed.  The array should be in 
//                  the form of the following.
//
//                  array(
//                      'tnid'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No tenant specified'),
//                      'source'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>''),
//                      ...
//                  );
// 
// Returns
// -------
//
function ciniki_core_parseArgs(&$ciniki, $tnid, $raw_args, $arg_info) {
    $args = array();

    //
    // Check if tnid is specified, and if so, load the tenant settings
    //
    if( $tnid > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
        $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $intl_timezone = $rc['settings']['intl-default-timezone'];
        date_default_timezone_set($intl_timezone);
        $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
        $intl_currency = $rc['settings']['intl-default-currency'];
    } else {
        $intl_timezone = 'America/Toronto';
    }

    foreach($arg_info as $arg => $options) {
        $msg = 'Missing argument';
        $invalid_msg = $msg;
        if( isset($options['name']) ) {
            $msg = "You must specify a " . $options['name'] . "";
            $invalid_msg = "Invalid " . $options['name'] . " format";
        } elseif( $options['errmsg'] ) {
            $msg = $options['errmsg'];
            $invalid_msg = $msg;
        }

        //
        // Check if the argument exists
        //
        if( isset($raw_args[$arg]) ) {
            //
            // Check for a blank argument, and if it's allowed
            //
            if( $raw_args[$arg] == '' && isset($options['blank']) && $options['blank'] != 'yes' ) {
                return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.core.122', 'msg'=>"$msg", 'pmsg'=>"Argument: $arg missing"));
            }

            if( isset($options['type']) && $options['type'] == 'idlist' ) {
                $args[$arg] = array();
                if( $raw_args[$arg] != '' ) {
                    $list = explode(',', $raw_args[$arg]);
                    // Typecast all entries as (int) so they are dealt with as ID's
                    foreach($list as $i) {
                        $args[$arg][] = (int)$i;
                    }
                }
            } elseif( isset($options['type']) && $options['type'] == 'list' ) {
                if( isset($options['delimiter']) && $options['delimiter'] != '' ) {
                    $list = explode($options['delimiter'], $raw_args[$arg]);
                } else {
                    $list = explode(',', $raw_args[$arg]);
                }
                $args[$arg] = array();
                foreach($list as $i) {
                    $args[$arg][] = $i;
                }
            } elseif( isset($options['type']) && $options['type'] == 'objectlist' ) {
                $list = explode(',', $raw_args[$arg]);
                $args[$arg] = array();
                foreach($list as $i) {
                    list($object, $object_id) = explode(':', $i);
                    $args[$arg][] = array('object'=>$object, 'id'=>$object_id);
                }
            } elseif( isset($options['type']) && $options['type'] == 'json' ) {
                $args[$arg] = json_decode($raw_args[$arg], true);
            } elseif( isset($options['type']) && $options['type'] == 'date' && $raw_args[$arg] != '' ) {
                $raw_args[$arg] = preg_replace('/Janurary/', 'January', $raw_args[$arg]);
                $raw_args[$arg] = preg_replace('/(Sun|Mon|Tue|Wed|Thu|Fri|Sat) /', '', $raw_args[$arg]);
                if( $raw_args[$arg] == 'now' || $raw_args[$arg] == 'today' ) {
                    $args[$arg] = strftime("%Y-%m-%d");
                } elseif( $raw_args[$arg] == 'tomorrow' ) {
                    $args[$arg] = strftime("%Y-%m-%d", time()+86400);
                } else {
                    $ts = strtotime($raw_args[$arg]);
                    if( $ts === FALSE ) { // Removed check for < 1 as negative timestamps work || $ts < 1 ) {
                        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.core.123', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid date format $ts"));
                        
                    } else {
                        $args[$arg] = strftime("%Y-%m-%d", $ts);
                    }
                }
            } elseif( isset($options['type']) && $options['type'] == 'time' && $raw_args[$arg] != '' ) {
                if( $raw_args[$arg] == 'now' || $raw_args[$arg] == 'today' ) {
                    $args[$arg] = strftime("%H:%M:%S");
                } else {
                    $ts = strtotime($raw_args[$arg]);
                    if( $ts === FALSE || $ts < 1 ) {    
                        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.core.124', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid date format"));
                        
                    } else {
                        $args[$arg] = strftime("%H:%M:%S", $ts);
                    }
                }
            } 
            elseif( isset($options['type']) && $options['type'] == 'datetime' && $raw_args[$arg] != '' ) {
                $raw_args[$arg] = preg_replace('/Janurary/', 'January', $raw_args[$arg]);
                if( $raw_args[$arg] == 'now' ) {
                    $args[$arg] = strftime("%Y-%m-%d %H:%M");
                } else {
                    $ts = strtotime($raw_args[$arg]);
                    if( $ts === FALSE ) {
                        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.core.125', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid datetime format"));
                    } else {
                        $args[$arg] = strftime("%Y-%m-%d %H:%M", $ts);
                    }
                }
            } 
            elseif( isset($options['type']) && $options['type'] == 'datetimetoutc' && $raw_args[$arg] != '' ) {
//              ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'timezoneOffset');
//              // UTC timezone offset in seconds
//              $utc_offset = ciniki_tenants_timezoneOffset($ciniki, 'seconds');
//              date_default_timezone_set('America/Toronto');
                $raw_args[$arg] = preg_replace('/Janurary/', 'January', $raw_args[$arg]);
                if( isset($options['trim']) && $options['trim'] == 'yes' ) {
                    $raw_args[$arg] = trim($raw_args[$arg]);
                }
                if( $raw_args[$arg] == '' && isset($options['blank']) && $options['blank'] == 'yes' ) {
                    $args[$arg] = '';
                }
                elseif( $raw_args[$arg] == 'now' ) {
                    $dt = new DateTime('now', new DateTimeZone('UTC'));
                    $args[$arg] = $dt->format('Y-m-d H:i:s');
//                  $args[$arg] = strftime("%Y-%m-%d %H:%M:%S");
                } else {
                    if( $raw_args[$arg] != '' 
                        && isset($options['defaulttime']) 
                        && !preg_match("/[0-9]?[0-9]:[0-9][0-9]/", $raw_args[$arg]) 
                        && !preg_match("/(pm|Pm|PM|am|Am|AM)/", $raw_args[$arg]) 
                        ) {
                        $raw_args[$arg] .= ' ' . $options['defaulttime'];
                    }
                    $ts = strtotime($raw_args[$arg]);
                    if( $ts === FALSE || $ts < 1 ) {
                        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.core.126', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid datetime format"));
                    } else {
                        $dt = new DateTime("@".$ts, new DateTimeZone($intl_timezone));
                        $args[$arg] = $dt->format('Y-m-d H:i:s');
//                      $args[$arg] = strftime("%Y-%m-%d %H:%M:%S", $ts - $utc_offset);
                    }
                }
            } 
            elseif( isset($options['type']) && $options['type'] == 'currency' && $raw_args[$arg] != '' ) {
                $args[$arg] = preg_replace('/ /', '', $raw_args[$arg]);
                if( $args[$arg] != '' ) {
                    if( ($intl_currency == 'CAD' || $intl_currency == 'USD') && $args[$arg][0] == '-' && $args[$arg][1] != '$' ) {
                        $args[$arg] = str_replace('-', '-$', $args[$arg]);
                    } elseif( ($intl_currency == 'CAD' || $intl_currency == 'USD') && $args[$arg][0] != '$' ) {
                        $args[$arg] = '$' . $args[$arg];
                    } else {
                        $args[$arg] = $args[$arg];
                    }
                    $amt = numfmt_parse_currency($intl_currency_fmt, $args[$arg], $intl_currency);
                    if( $amt === FALSE ) {
                        return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.core.127', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg invalid currency format"));
                    }
                    $args[$arg] = $amt;
                }
            }
            elseif( isset($options['type']) && $options['type'] == 'int' && preg_match('/^\d+$/',$raw_args[$arg]) ) {
                $args[$arg] = (int)$raw_args[$arg];
            } 
            elseif( isset($options['type']) && $options['type'] == 'float' && preg_match('/^\d+(\.\d+)?$/',$raw_args[$arg]) ) {
                $args[$arg] = (float)$raw_args[$arg];
            }
            elseif( isset($options['type']) && $options['type'] == 'number' ) {
                $args[$arg] = preg_replace("/[^0-9\.]/", '', $raw_args[$arg]);
            } 
            elseif( isset($options['type']) && $options['type'] == 'percent' ) {
                $args[$arg] = preg_replace("/[^0-9\.]/", '', $raw_args[$arg]);
                //
                // Convert to decimal value from percent value
                //
                if( $args[$arg] != '' && $args[$arg] != 0 ) {
                    $args[$arg] = ($args[$arg]/100);
                }
            } 
            else {
                if( !isset($options['trim']) ) {
                    $args[$arg] = trim($raw_args[$arg]);
                } else {
                    $args[$arg] = $raw_args[$arg];
                }
            }

            if( isset($options['trim']) && $options['trim'] == 'yes' ) {
                $args[$arg] = trim($args[$arg]);
            } elseif( isset($options['trimblanks']) && $options['trimblanks'] == 'yes' ) {
                $args[$arg] = trim($args[$arg]);
            }
            
            // Check for the text string null, and convert to specified string
            if( isset($options['null']) && $args[$arg] == 'null' ) {
                $args[$arg] = $options['null'];
            }

            // Check if there is a list of valid options to accept
            if( isset($options['validlist']) && isset($args[$arg]) && !in_array($args[$arg], $options['validlist']) ) {
                return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.core.128', 'msg'=>"$invalid_msg", 'pmsg'=>"Argument: $arg not an acceptable input"));
            }
        } 
        
        //
        // If the argument does not exist, check what should be done
        //
        else {
            //
            // Return an error if this argument is required
            //
            if( $options['required'] == 'yes' ) {
                return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.core.129', 'msg'=>"$msg", 'pmsg'=>"Argument: $arg missing."));
            } 
        
            //
            // The argument is not required, check for a default value to assign
            //
            if( isset($options['default']) ) {
                if( isset($options['type']) && $options['type'] == 'date' 
                    && ($options['default'] == 'now' || $options['default'] == 'today') ) {
                    $dt = new DateTime('now', new DateTimeZone($intl_timezone));
                    $args[$arg] = $dt->format('Y-m-d');
                } elseif( isset($options['type']) && $options['type'] == 'datetime' 
                    && ($options['default'] == 'now' || $options['default'] == 'today') ) {
                    $dt = new DateTime('now', new DateTimeZone($intl_timezone));
                    $args[$arg] = $dt->format('Y-m-d H:i');
                } elseif( isset($options['type']) && $options['type'] == 'datetimetoutc' 
                    && ($options['default'] == 'now' || $options['default'] == 'today') ) {
                    $dt = new DateTime('now', new DateTimeZone('UTC'));
                    $args[$arg] = $dt->format('Y-m-d H:i:s');
                } else {
                    $args[$arg] = $options['default'];
                }
            }

            // If no default value, then the arg is not added to the list
        }
    }

    return array('stat'=>'ok', 'args'=>$args);
}
?>
