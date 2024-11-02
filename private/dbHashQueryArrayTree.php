<?php
//
// Description
// -----------
// This function will query the database, and build a hash tree based
// on the elements of the $tree variable.  
// 
// FIXME: add documentation
//
// Info
// ----
// status:          beta
//
// Arguments
// ---------
// ciniki:          
// strsql:          The SQL string to query the database.
// module:          The name of the module for the transaction, which should include the 
//                  package in dot notation.  Example: ciniki.artcatalog
// container_name:  The name of the xml/hash tag to return the data under, 
//                  when there is only one row returned.
// col_name:        The column to be used as the row ID within the result.
//
function ciniki_core_dbHashQueryArrayTree(&$ciniki, $strsql, $module, $tree) {
    //
    // Open a connection to the database if one doesn't exist.  The
    // dbConnect function will return an open connection if one 
    // exists, otherwise open a new one
    //
    $rc = ciniki_core_dbConnect($ciniki, $module);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $dh = $rc['dh'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbParseAge');

    //
    // Prepare and Execute Query
    //
    $start_time = microtime(true);
    try {
        $result = mysqli_query($dh, $strsql);
    } catch(mysqli_sql_exception $e) {
        error_log("SQLERR: [" . $e->getCode() . "] " . $e->getMessage() . " -- '$strsql'");
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.core.61', 'msg'=>'Database Error', 'pmsg'=>$e->getMessage()));
    }
    $mid_time = microtime(true);

    //
    // Check if any rows returned from the query
    //
    $rsp = array('stat'=>'ok');
    $rsp['num_rows'] = 0;
    $rsp['num_cols'] = 0;

    //
    // Build array of rows
    //
    $prev = array();
    $lists = array();
    $dlists = array();
    $prev_row = null;
    $num_elements = array();
    for($i=0;$i<count($tree);$i++) {
        $prev[$i] = null;
        $num_elements[$i] = 0;
    }
    while( $row = mysqli_fetch_assoc($result) ) {
        // 
        // Check if we have anything new at each depth
        //
        $data = &$rsp;
        for($i=0;$i<count($tree);$i++) {
            if( $i > 0 ) {
                // $data = $data[$tree[$i]['container'];
            }
            // error_log($tree[$i]['fname'] . ' = ' . $row[$tree[$i]['fname']]);
            if( is_null($row[$tree[$i]['fname']]) ) {
                continue;
            }
            // Are we at the limit, then stop save any other SQL processing
            if( isset($tree[$i]['limit']) && $tree[$i]['limit'] > 0 && $num_elements[$i] >= $tree[$i]['limit'] ) {
                break;
            }
            if( $prev[$i] !== $row[$tree[$i]['fname']] ) {
                $lists[$i] = array();
                $dlists[$i] = array();
                // Reset all num_element this depth and below
                for($j=$i+1;$j<count($tree);$j++) {
                    $num_elements[$j] = 0;
                    $prev[$j] = null;
                }
                // Check if container exists
                if( !isset($data[$tree[$i]['container']]) ) {
                    $data[$tree[$i]['container']] = array();
                }
                $data[$tree[$i]['container']][$num_elements[$i]] = array();
                // Copy Data
                if( isset($tree[$i]['fields']) ) {
                    foreach($tree[$i]['fields'] as $field_id => $field) {
                        // Check if the field name from the SQL should be translated to another name in the array
                        // This is used when tnid should become id in the data structure (example).
                        if( !is_string($field_id) && is_int($field_id) ) {
                            // Field is in integer and should not be mapped
                            $field_id = $field;
                        }
                        //
                        // Items that are mapped to another value
                        //
                        if( isset($tree[$i]['maps']) && isset($tree[$i]['maps'][$field_id]) ) {
                            //
                            // Check if the value is specified in the mapped array for this field
                            // If no mapped value specified, check for blank index
                            // Last resort, set it to current value
                            //
                            if( isset($tree[$i]['maps'][$field_id][$row[$field]])
                                && is_array($tree[$i]['maps'][$field_id][$row[$field]])
                                && isset($tree[$i]['maps'][$field_id][$row[$field]]['field']) ) {
                                // if array of single/plural names, decide which it is
                                $decision_field = $tree[$i]['maps'][$field_id][$row[$field]]['field'];
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $tree[$i]['maps'][$field_id][$row[$field]][($row[$decision_field]!=1?'p':'s')];
                            } elseif( isset($tree[$i]['maps'][$field_id][$row[$field]]) ) {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $tree[$i]['maps'][$field_id][$row[$field]];
                            } elseif( isset($tree[$i]['maps'][$field_id]['']) ) {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $tree[$i]['maps'][$field_id][''];
                            } else {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $row[$field];
                            }
                        } elseif( isset($tree[$i]['maps']) && isset($tree[$i]['maps'][$field]) ) {
                            //
                            // Check if the value is specified in the mapped array for this field
                            // If no mapped value specified, check for blank index
                            // Last resort, set it to current value
                            //
                            if( isset($tree[$i]['maps'][$field][$row[$field]])
                                && is_array($tree[$i]['maps'][$field][$row[$field]])
                                && isset($tree[$i]['maps'][$field][$row[$field]]['field']) ) {
                                // if array of single/plural names, decide which it is
                                $decision_field = $tree[$i]['maps'][$field][$row[$field]]['field'];
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $tree[$i]['maps'][$field][$row[$field]][($row[$decision_field]!=1?'p':'s')];
                            } elseif( isset($tree[$i]['maps'][$field][$row[$field]]) ) {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $tree[$i]['maps'][$field][$row[$field]];
                            } elseif( isset($tree[$i]['maps'][$field]['']) ) {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $tree[$i]['maps'][$field][''];
                            } else {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $row[$field];
                            }
                        } 
                        //
                        // Check if currency should be formatted
                        //
                        elseif( isset($tree[$i]['currency']) && isset($tree[$i]['currency'][$field_id]['fmt']) && isset($tree[$i]['currency'][$field_id]['currency']) ) {
                            $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = numfmt_format_currency($tree[$i]['currency'][$field_id]['fmt'], $row[$field], $tree[$i]['currency'][$field_id]['currency']);
                        }
                        //
                        // Check if North American price format
                        //
                        elseif( isset($tree[$i]['naprices']) && in_array($field_id, $tree[$i]['naprices']) && $row[$field] != '' ) {
                            $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = '$' . number_format($row[$field], 2);
                        }
                        //
                        // Check if a date should be formatted
                        //
                        elseif( isset($tree[$i]['dtformat']) && isset($tree[$i]['dtformat'][$field_id]) ) {
                            if( $row[$field] == '0000-00-00 00:00:00' || $row[$field] == '0000-00-00' || $row[$field] == '' ) {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = '';
                            } else {
                                $date = new DateTime($row[$field], new DateTimeZone('UTC'));
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = 
                                    $date->format($tree[$i]['dtformat'][$field_id]);
                            }
                        }
                        //
                        // Check if utc dates should be converted to local timezone
                        //
                        elseif( isset($tree[$i]['utctotz']) && isset($tree[$i]['utctotz'][$field_id]) ) {
                            if( $row[$field] == '0000-00-00 00:00:00' || $row[$field] == '0000-00-00' || $row[$field] == '' ) {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = '';
                            } else {
                                $date = new DateTime($row[$field], new DateTimeZone('UTC'));
                                $date->setTimezone(new DateTimeZone($tree[$i]['utctotz'][$field_id]['timezone']));
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = 
                                    $date->format($tree[$i]['utctotz'][$field_id]['format']);
                            }
                        }
                        elseif( isset($tree[$i]['utctodate']) && isset($tree[$i]['utctodate'][$field_id]) ) {
                            if( $row[$field] == '0000-00-00 00:00:00' || $row[$field] == '0000-00-00' || $row[$field] == '' ) {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = '';
                            } else {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = 
                                    new DateTime($row[$field], new DateTimeZone($tree[$i]['utctodate'][$field_id]));
                            }
                        }
                        elseif( isset($tree[$i]['utctots']) && in_array($field_id, $tree[$i]['utctots']) ) {
                            if( $row[$field] == '0000-00-00 00:00:00' || $row[$field] == '0000-00-00' || $row[$field] == '' ) {
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = '0';
                            } else {
                                $date = new DateTime($row[$field], new DateTimeZone('UTC'));
                                $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = 
                                    $date->format('U');
                            }
                        }
                        //
                        // Check if field is percent value
                        //
                        elseif( isset($tree[$i]['percents']) && in_array($field_id, $tree[$i]['percents']) ) {
                            $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = (float)($row[$field]*100) . '%';
                        }
                        elseif( $field == 'age' || substr($field, 0, 4) == 'age_' ) {
                            $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = ciniki_core_dbParseAge($ciniki, $row[$field]);
                        }
                        elseif( isset($tree[$i]['flags']) && isset($tree[$i]['flags'][$field_id]) ) {
                            $text = '';
                            foreach($tree[$i]['flags'][$field_id] as $bitmask => $flagtext) {
                                if( ($row[$field]&$bitmask) == $bitmask ) {
                                    $text .= ($text!=''?', ':'') . $flagtext;
                                }
                            }
                            $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $text;
                        }
                        elseif( isset($tree[$i]['lists']) && in_array($field_id, $tree[$i]['lists']) ) {
                            //
                            // Deal with lists same as if prev row is same as current row
                            //
                            if( !isset($lists[$i][$field]) ) {
                                $lists[$i][$field] = array();
                            }
                            if( $row[$field] != null && !in_array($row[$field], $lists[$i][$field]) ) {
                                $lists[$i][$field][] = $row[$field];
                                if( isset($data[$tree[$i]['container']][$num_elements[$i]][$field_id]) ) {
                                    $data[$tree[$i]['container']][$num_elements[$i]][$field_id] .= ',' . $row[$field];
                                } else {
                                    $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $row[$field];
                                }
                            }
                        }
                        elseif( isset($tree[$i]['dlists'][$field_id]) ) {
                            //
                            // Deal with lists same as if prev row is same as current row
                            //
                            if( !isset($dlists[$i][$field]) ) {
                                $dlists[$i][$field] = array();
                            }
                            if( $row[$field] != null && !in_array($row[$field], $dlists[$i][$field]) ) {
                                $dlists[$i][$field][] = $row[$field];
                                if( isset($data[$tree[$i]['container']][$num_elements[$i]][$field_id]) ) {
                                    $data[$tree[$i]['container']][$num_elements[$i]][$field_id] .= $tree[$i]['dlists'][$field] . $row[$field];
                                } else {
                                    $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $row[$field];
                                }
                            }
                        }
                        elseif( isset($tree[$i]['unserialize']) && in_array($field_id, $tree[$i]['unserialize']) ) {
                            $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $row[$field];
                            if( $row[$field] != '' ) {
                                $values = unserialize($row[$field]);
                                foreach($values as $k => $v) {
                                    $data[$tree[$i]['container']][$num_elements[$i]][$k] = $v;
                                }
                            }
                        }
                        
                        // Normal item
                        else {
                            $data[$tree[$i]['container']][$num_elements[$i]][$field_id] = $row[$field];
                        }
                    }
                }
                //
                // Check for fields to be flattened out into a single item from multirow results
                //
                if( isset($tree[$i]['details']) ) {
                    foreach($tree[$i]['details'] as $key_name => $key_value) {
                        $data[$tree[$i]['container']][$num_elements[$i]][$row[$key_name]] = $row[$key_value];
                    }
                }

                $data = &$data[$tree[$i]['container']][$num_elements[$i]];
                $num_elements[$i]++;
            }
            else {
                foreach($tree[$i]['fields'] as $field) {
                    //
                    // Provide the ability to count items
                    //
                    if( isset($tree[$i]['countlists']) && in_array($field, $tree[$i]['countlists']) ) {
                        if( $prev_row != null && $prev_row[$field] == $row[$field] ) {
                            if( preg_match('/ \(([0-9]+)\)$/', $data[$tree[$i]['container']][$num_elements[$i]-1][$field], $matches) ) {
                                $data[$tree[$i]['container']][$num_elements[$i]-1][$field] = preg_replace('/ \([0-9]+\)$/', ' (' . (intval($matches[1])+1) . ')', $data[$tree[$i]['container']][$num_elements[$i]-1][$field]);
                            } else {
                                $data[$tree[$i]['container']][$num_elements[$i]-1][$field] .= ' (2)';
                            }
                        } else {
                            $data[$tree[$i]['container']][$num_elements[$i]-1][$field] .= ',' . $row[$field];
                        }
                    }

                    if( isset($tree[$i]['idlists']) && in_array($field, $tree[$i]['idlists']) 
                        && $prev_row != null && $prev_row[$field] != $row[$field] ) {
                        //
                        // Check if field was declared in fields array, if not it can be added now
                        //
                        if( isset($data[$tree[$i]['container']][$num_elements[$i]-1][$field]) ) {
                            $data[$tree[$i]['container']][$num_elements[$i]-1][$field] .= ',' . $row[$field];
                        } else {
                            $data[$tree[$i]['container']][$num_elements[$i]-1][$field] = $row[$field];
                        }
                    }

                    if( isset($tree[$i]['lists']) && in_array($field, $tree[$i]['lists']) ) {
//                        && $prev_row != null && $prev_row[$field] != $row[$field] ) {
                        //
                        // Check if field was declared in fields array, if not it can be added now
                        //
                        if( !isset($lists[$i][$field]) ) {
                            $lists[$i][$field] = array();
                        }
                        if( $row[$field] != null && !in_array($row[$field], $lists[$i][$field]) ) {
                            $lists[$i][$field][] = $row[$field];
                            if( isset($data[$tree[$i]['container']][$num_elements[$i]-1][$field]) ) {
                                $data[$tree[$i]['container']][$num_elements[$i]-1][$field] .= ',' . $row[$field];
                            } else {
                                $data[$tree[$i]['container']][$num_elements[$i]-1][$field] = $row[$field];
                            }
                        }
                    }
                    //
                    // Lists which have delimiters specified
                    //
                    if( isset($tree[$i]['dlists']) && array_key_exists($field, $tree[$i]['dlists'])  ) {
//                        && $prev_row != null && $prev_row[$field] != $row[$field] ) {
                        //
                        // Check if field was declared in fields array, if not it can be added now
                        //
                        if( !isset($dlists[$i][$field]) ) {
                            $dlists[$i][$field] = array();
                        }
                        if( $row[$field] != null && !in_array($row[$field], $dlists[$i][$field]) ) {
                            $dlists[$i][$field][] = $row[$field];
                            if( isset($data[$tree[$i]['container']][$num_elements[$i]-1][$field]) ) {
                                $data[$tree[$i]['container']][$num_elements[$i]-1][$field] .= $tree[$i]['dlists'][$field] . $row[$field];
                            } else {
                                $data[$tree[$i]['container']][$num_elements[$i]-1][$field] = $row[$field];
                            }
                        }
                    }
                    // 
                    // Items can be in lists and summed at the same time
                    //
                    if( isset($tree[$i]['sums']) && in_array($field, $tree[$i]['sums']) ) {
                        //
                        // Check if field was declared in fields array, if not it can be added now
                        //
                        if( isset($data[$tree[$i]['container']][$num_elements[$i]-1][$field]) ) {
                            $data[$tree[$i]['container']][$num_elements[$i]-1][$field] += $row[$field];
                        } else {
                            $data[$tree[$i]['container']][$num_elements[$i]-1][$field] = $row[$field];
                        }
                    }
                }

                //
                // Check for fields to be flattened out into a single item from multirow results
                //
                if( isset($tree[$i]['details']) ) {
                    foreach($tree[$i]['details'] as $key_name => $key_value) {
                        $data[$tree[$i]['container']][$num_elements[$i]-1][$row[$key_name]] = $row[$key_value];
                    }
                }

                $data = &$data[$tree[$i]['container']][$num_elements[$i]-1];
            }
            $prev[$i] = $row[$tree[$i]['fname']];
        }
        $prev_row = $row;
    }

    mysqli_free_result($result);
    $end_time = microtime(true);

    if( isset($ciniki['config']['ciniki.core']['database.log.querytimes'])
        && $ciniki['config']['ciniki.core']['database.log.querytimes']
        ) {
        error_log('[' . round($mid_time-$start_time, 2) . ':' . round($end_time-$mid_time, 2) . '] ' . $strsql);
    }

    return $rsp;
}
?>
