<?php
//
// Description
// -----------
// This function will generate an excel file using PHPSpreadsheet
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
require_once($ciniki['config']['core']['lib_dir'] . '/vendor/autoload.php');

function ciniki_core_excelGenerate(&$ciniki, $tnid, $args) {


    $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
//    $cell = new \PhpOffice\PhpSpreadsheet\Cell\Coordinate();

    $sheet_num = 0;
    foreach($args['sheets'] as $sid => $sheet) {
        if( $sheet_num == 0 ) {
            $spreadsheet = $excel->getActiveSheet();
        } else {
            $spreadsheet = $excel->createSheet();
        }
        if( isset($sheet['label']) && $sheet['label'] != '' ) {
            $spreadsheet->setTitle($sheet['label']);
        }

        //
        // Add the headers
        //
        $cur_col = 1;
        $cur_row = 1;
        foreach($sheet['columns'] as $column) {
            $spreadsheet->setCellValue([$cur_col, $cur_row], $column['label']);
            $cur_col++;
        }
        $ltr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cur_col-1);
        $spreadsheet->getStyle("A1:{$ltr}1")->getFont()->setBold(true);

        $cur_row++;
        $cur_col = 1;
        $first_data_row = $cur_row;
        $last_data_rorw = $cur_row;
       
        //
        // Add the data
        //
        foreach($sheet['rows'] as $row) {
            foreach($sheet['columns'] as $column) {
                $ltr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cur_col);
                if( !isset($row[$column['field']]) ) {
                    $cur_col++;
                    continue;
                }
                if( isset($column['format']) && $column['format'] == 'currency' ) {
                    $spreadsheet->setCellValue([$cur_col, $cur_row], preg_replace("/[^0-9\.]/", '', $row[$column['field']]));
                    $spreadsheet->getStyle($ltr . $cur_row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD);
                } else {
                    $spreadsheet->setCellValue([$cur_col, $cur_row], $row[$column['field']]);
                }
                $cur_col++;
            }
            $cur_col = 1;
            $last_data_row = $cur_row;
            $cur_row++;
        }

        //
        // Add the footer/totals
        //
        foreach($sheet['columns'] as $column) {
            $ltr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cur_col);
            if( isset($column['footer']) ) {
                if( $column['footer'] == 'sum' ) {
                    $spreadsheet->setCellValue([$cur_col, $cur_row], "=SUM({$ltr}2:{$ltr}{$last_data_row})");
                } else {
                    $spreadsheet->setCellValue([$cur_col, $cur_row], $column['footer']);
                }
                if( isset($column['format']) && $column['format'] == 'currency' ) {
                    $spreadsheet->getStyle($ltr . $cur_row)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_CURRENCY_USD);
                }
            }
            $spreadsheet->getStyle("{$ltr}{$cur_row}")->getFont()->setBold(true);
            $spreadsheet->getColumnDimension($ltr)->setAutoSize(true);
            $cur_col++;
        }
        $spreadsheet->freezePane("A" . $first_data_row);
        $sheet_num++;
    }

    $excel->setActiveSheetIndex(0);

    if( isset($args['download']) && $args['download'] == 'yes' && isset($args['filename']) 
        && isset($args['format']) && $args['format'] == 'xls' 
        ) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $args['filename'] . '"');
        header('Cache-Control: max-age=0');

        $xlsWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xls($excel);

        $xlsWriter->save('php://output');
        return array('stat'=>'exit');
    } elseif( isset($args['download']) && $args['download'] == 'yes' && isset($args['filename']) ) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $args['filename'] . '"');
        header('Cache-Control: max-age=0');

        $xlsxWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($excel);

        $xlsxWriter->save('php://output');
        return array('stat'=>'exit');
    }
    if( isset($args['save']) && $args['save'] == 'yes' && isset($args['filename']) ) {
        $xlsxWriter = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($excel);

        $xlsxWriter->save($args['filename']);
        return array('stat'=>'exit');
    }
    return array('stat'=>'ok', 'excel'=>$excel);
}
?>
