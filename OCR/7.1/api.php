<?php

header("Access-Control-Allow-Orgin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");

$serverMethod = $_SERVER['REQUEST_METHOD'];

if($serverMethod == 'POST'){

    $headerAuth = getallheaders();

    if($headerAuth['api-key']=="d2ef76102412c7aea7b61484557518ef"){ 

        $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["file"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            //echo "<text>"."File is an image - " . $check["mime"] . "."."</text>";
            $uploadOk = 1;
        } else {
            http_response_code(400);
            $response = array("responseType"=>"Error","response"=>"Invalid file type");
            echo json_encode($response);
            return;
        }
    }
    // Check if file already exists
    //if (file_exists($target_file)) {
    //    echo "Sorry, file already exists.";
    //    $uploadOk = 0;
    //}
    // Check file size
        if ($_FILES["file"]["size"] > 100000000) {

        echo "<text>"."Sorry, your file is too large."."</text>";exit();
        return;
    }
    
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
        http_response_code(400);
        $response = array("responseType"=>"Error","response"=>"Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.");
        echo json_encode($response);
        return;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        http_response_code(400);
        $response = array("responseType"=>"Error","response"=>"File upload failed.");
        echo json_encode($response);
        return;
    // if everything is ok, try to upload file
    }

    //upload image
    if (!move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        http_response_code(400);
        $response = array("responseType"=>"Error","response"=>"Error occured while uploading your file.");
        echo json_encode($response); 
        return;
    }

    $card_type = $_POST['cardType'];
    $card_type_id = -1;
    if ($card_type == '2')
        $card_type_id = 0;
    else if ($card_type == '3')
        $card_type_id = 1;
	else if ($card_type == '4')
        $card_type_id = 1;
	
    else if ($card_type == '1')
        $card_type_id = 2;

    //image load
    $image = file_get_contents($target_file);
    unlink($target_file);
    $dic = file_get_contents("db/mMQDF_f_Passport_bottom_Gray.dic");
    $dic1 = file_get_contents("db/mMQDF_f_Passport_bottom.dic");
    $trained_data = file_get_contents("db/eng.dat");
    $license = file_get_contents("db/key.license");
   // $fdata_path = realpath("db/fdata.nn");

    if (!extension_loaded("accuraMRZ"))
    {
        http_response_code(500);
        $response = array("responseType"=>"Error","response"=>"Failed to load CARDREC extension.");
        echo json_encode($response);
        return;
    }
    
    gc_enable();

    $engine = new \Mrz($card_type_id);

    if ($license == null)
    {
        http_response_code(400);
        $response = array("responseType"=>"Error","response"=>"License file is missing.");
        echo json_encode($response);
        return;
    }
    
    $devinfo = $engine->getDevInfo();
   // $ret = $engine->loadDB($dic, $dic1, $trained_data, $license, $fdata_path);
    
	$ret = $engine->loadDB($dic, $dic1, $trained_data, $license);
	
	$strErr = $engine->getErrorMsg();
    if ($ret < 0)
    {
        //echo "<text>".$devinfo."<br />".$strErr."</text>";
        $resDev = json_encode($devinfo);
        $strHDD = $resDev->{'HDD'};
        $strDomain = $resDev->{'Domain'};
        if (strpos($strErr, 'Invalid') === false){
        	http_response_code(400);
            $response = array("responseType"=>"Error","response"=>$strErr);
            echo json_encode($response);
        } else {
        	http_response_code(400);
            $response = array("responseType"=>"Error","response"=>array("HDD Serial"=>$strHDD,"domain"=>$strDomain,"message"=>$strErr));
            echo json_encode($response);
        }	
        return;
    }
    
    $image = imagecreatefromstring($image);
    

    $width = imagesx($image);
    $height = imagesy($image);

    //$colors = array();
    $str = "";
    for ($y = 0; $y < $height; $y++) {
    //$y_array = array();
    for ($x = 0; $x < $width; $x++) {
        $rgb = imagecolorat($image, $x, $y);
        $b = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $r = $rgb & 0xFF;
        //$x_array = array($r, $g, $b);
        $str.= chr($r).chr($g).chr($b);
        //$str.= strval($r).",".strval($g).",".strval($b).",";
        //$y_array[] = $x_array;
    }
    //$colors[] = $y_array;
    }

    //echo "<text>"."result = ".$str."</text>";
    //return;   

    $ret = $engine->doRecognize($str, $width, $height);//image

    if ($ret <= 0)
    {
        $result_face = $engine->getFaceImage(); //face image
        $result_img = $engine->getCardImage();
        //echo "<text>"."Failed to recognize"."</text>";
    	//echo "<face>"."data:image/jpg;base64,".base64_encode($result_face)."</face>";
    	//echo "<card>"."data:image/jpg;base64,".base64_encode($result_img)."</card>";
    	http_response_code(400);
        $response = array("responseType"=>"Error","response"=>array("message"=>"Failed to recognize","face"=>$result_face,"card"=>$result_img));
        echo json_encode($response);
        return;
    }
    //echo 'success to recognize';

    $result = $engine->getResult(); //recognition result string
    $order   = array("\r\n", "\n", "\r","");
    $replace = '';
    $result_rep = str_replace($order, $replace, $result);
    $result_rep = str_replace("<","&lt;",$result_rep);
    $res_obj = json_decode($result_rep);
    // $res_obj = $result_rep;
    

    if ($card_type == "1")
        $parse_text = parse_passport($res_obj, $ret);
    else if ($card_type == "2")
        $parse_text = parse_pan($res_obj);
    else if ($card_type == "3")
        $parse_text = parse_aadhar($res_obj);
    else if ($card_type == "4")
        $parse_text = parse_aadhar($res_obj);
    	
    
    $result_face = $engine->getFaceImage(); //face image
    // $result_img = $engine->getCardImage(); //card image
    $parse_text['image'] = "data:image/jpg;base64,".base64_encode($result_face);
    http_response_code(200);
    $response = array("responseType"=>"Success","response"=>$parse_text);
    echo json_encode($response);
    
	$result_text = null;
	$result_face = null;
	$result_img = null;
	$engine->freeEngine();
	$engine = null;

    return;

    }else {
        http_response_code(400);
        $response = array("responseType"=>"Error","response"=>"Invalid Api Key");
        echo json_encode($response);
        return;
    }
    
        
}else {
    http_response_code(405);
    $response = array("responseType"=>"Error","response"=>"Method not allowed");
    echo json_encode($response);
}

function parse_passport($arg, $flag)
{
        
        $lines = str_replace("&lt;","<",$arg->{'Lines'});
        $doctype = $arg->{'DocType'};
        $country = $arg->{'Country'};
        $surname = $arg->{'Surname'};
        $givenname = $arg->{'Givename'};
        $docnumber = $arg->{'DocNumber'}; //Passport Number
        $passportchecksum = $arg->{'CheckNumber'} ?? ''; //Check Number
        $nationality = $arg->{'Nationality'};
        $birth = $arg->{'Birth'};
        $birthchecksum = $arg->{'BirthCheckNumber'};//Birth Check Number
        $sex = $arg->{'Sex'};
        $expirationdate = $arg->{'ExpirationDate'}; //Expiration Date
        $expirationchecksum = $arg->{'ExpirationCheckNumber'}; //Expiration Check Number
        $otherid = $arg->{'PersonalNumber'}; //Personal Number
        $otheridchecksum = $arg->{'PersonalNumberCheck'} ?? ''; //Personal Number Check
        $secondrowchecksum = $arg->{'SecondRowCheckNumber'}; //SecondRow Check Number
        
        

    	if ($flag > 1){
    		$mrz_result = "Incorrect Document \n";
    		$correctDoc="FALSE";
    	} else if($flag == 1) {
    		$mrz_result = "Correct Document \n";
    		$correctDoc = "TRUE";
    	}
        
        $res = array(
            "correctDocument"=>$correctDoc,
            "mrz"=>$lines,
            "documentType"=>$doctype,
            "country"=>$country,
            "surname"=>$surname,
            "givenName"=>$givenname,
            "documentNumber"=>$docnumber,
            "documentCheckNumber"=>$passportchecksum,
            "nationality"=>$nationality,
            "dateOfBirth"=>$birth,
            "dobCheckNumber"=>$birthchecksum,
            "gender"=>$sex,
            "exirtyDate"=>$expirationdate,
            "expirtyCheckNumber"=>$expirationchecksum,
            "otherId"=>$otherid,
            "otherIdCheckNumber"=>$otheridchecksum,
            "secondRowCheckNumber"=>$secondrowchecksum,
            "flag"=>$flag,
            "image"=>'',
        );
        return $res;
    }

    function parse_pan($arg)
    {
        $cardtype = $arg->{'Card'};
        $name = $arg->{'Name'};
        $fathername = $arg->{'FatherName'};
        $birthday = $arg->{'Birthday'};
        $pan = $arg->{'PAN'};

        $res = array(
            "cardType"=>$cardtype,
            "name"=>$name,
            "fatherName"=>$fathername,
            "dateOfBirth"=>$birthday,
            "panNo"=>$pan,
            "image"=>'',
        );
        
        return $res;
    }

    function parse_aadhar($arg)
    {
        $cardtype = $arg->{'Card'};
        if (strpos($cardtype, 'front') != false) //front
        {
            $name = $arg->{'Name'};
            $birthday = $arg->{'Birth'};
            $sex = $arg->{'Sex'};
            $ann = $arg->{'AAN'};

            $res = array(
                "cardType"=>$cardtype,
                "name"=>$name,
                "dateOfBirth"=>$birthday,
                "gender"=>$sex,
                "aadharCardNo"=>$ann,
                "image"=>'',
            );
            
            return $res;
        }
        else if (strpos($cardtype, 'back') != false)
        {
            $address = $arg->{'Address'};

            $result  = "Card : ".$cardtype."\n";
            $result .= $address;

            $res = array(
                "cardType"=>$cardtype,
                "address"=>$address,
                "image"=>'',
            );

            return $res;
        }

        return "";
    }
    
    function parse_india_passport($arg)
    {
        $cardtype = $arg->{'Card'};
        $fathername = $arg->{'FatherName'};
        $mothername = $arg->{'MotherName'};
        $name = $arg->{'Name'};
        $address = $arg->{'Address'};
        $passportno = $arg->{'PassportNo'};
        $passdate = $arg->{'Date'};
        $placeofissue = $arg->{'Placeofissue'};
        $fileno = $arg->{'FileNo'};


        $res = array(
            "cardType"=>$cardtype,
            "fatherName"=>$fathername,
            "motherName"=>$mothername,
            "name"=>$name,
            "address"=>$address,
            "passportNo"=>$passportno,
            "date"=>$passdate,
            "placeOfIssue"=>$placeofissue,
            "fileNo"=>$fileno,
            "image"=>'',
        );

        return $res;
    }

?>
