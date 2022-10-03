<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\PdfToText\Pdf;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Storage;
use App\Models\Produit;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;
use \Illuminate\Http\File;

class ConvertController extends Controller
{
    public function Convert(Request $request) {
        $ex = $request->file->getClientOriginalExtension();
        //file upload method
       if ($ex == "pdf") {
           try {
               $file = $request->file;
               $pdfParser = new Parser();
               $pdf = $pdfParser->parseFile($file->path());
               $pages  = $pdf->getPages();
               $pagecount = count($pages);
               $config = new \Smalot\PdfParser\Config();
               // An empty string can prevent words from breaking up
               $config->setHorizontalOffset('');
               // A tab can help preserve the structure of your document
               $config->setHorizontalOffset("\t");
               $parser = new \Smalot\PdfParser\Parser([], $config);
               echo "<strong>Total pages: $pagecount</strong> <br>";
               foreach ($pages as $page) {
                   # code...
                   $content = $pdf->getText();
                   $rd = str_replace(["\n\n"], " ", $content);
                   $headline = Str::headline($rd);
                   $lower = Str::lower($headline);
                }

            } catch (\Throwable $th) {
                throw $th;
            }

            //========specify wich text to grap================
        //    $slice4 = Str::of($headline)->after('Nom'); //al-mahdi ....
           $slice = Str::between($headline, 'Nom', 'Prénom');
           $slice1 = Str::of($headline)->after( 'Prénom');
           $slice2 = Str::between($headline, 'Début', 'Date');
           $slice3 = Str::between($headline, 'Fin', 'Langue');

           $truncated = Str::of($slice)->limit(100);
           $truncated1 = Str::of($slice1)->limit(20);
           $truncated2 = Str::of($slice2)->limit(100);
           $truncated3 = Str::of($slice3)->limit(100);
           $save = "Nom : $truncated  Prenom : $truncated1  Date Debut : $truncated2  Date fin : $truncated3";
           echo $save;

            //save output to resultpdf.txt========================================
           $myfile = fopen("resultpdf.txt", "w") or die("Unable to open file!");
            fwrite($myfile, $save);
            fclose($myfile);


       /* =================convert image to txt usinf tesseract===================================*/
       }else if($ex == "png"){
            $image = $request->file;
            $imagePath = Storage::disk('public')->putFile('image',$image);
            $ocr  = new TesseractOCR(public_path("storage/$imagePath"));
            $ocr->executable('C:\ProgramData\chocolatey\lib\capture2text\tools\Capture2Text\Utils\tesseract\tesseract.exe');
            //tesseract option==========================================
            $ocr->lang('fra');
            $ocr->psm(3);
            $ocr->SetRectangle(30, 86, 590, 100);
            $ocr->GetUTF8Text();
            $ocr->buildCommand();
            $timeout = 600;

            //start proccess========
            $text = $ocr->run($timeout);

            //========specify wich text to grap======

            // $text = str_replace(' ',"<br>",$text);
            $slice = Str::of($text)->after('10/2021'); //al-mahdi ....
            // // $slice1 = Str::of($rd)->after('Né le'); //aitounzar ....
            // // $slice2 = Str::of($rd)->after('nationale N'); //aitounzar ....
            // // $speace = substr_count($slice, ' ');
            $truncated = Str::of($slice)->limit(350);

            /*========= print result to browser ==================== */
            echo "<pre> . $truncated . </pre>";

            //save output to result.txt========================================
            $myfile = fopen("result.txt", "w") or die("Unable to open file!");
            fwrite($myfile, $truncated);
            fclose($myfile);

       }
    }

}
