<?php

    /*if (extension_loaded('ftp')) {
        echo "<br>custom support is loaded ";
    }else {
        echo "<br>custom support is NOT loaded ";
    }*/

    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
        if($check !== false) {
            echo "<text>"."File is an image - " . $check["mime"] . "."."</text>";
            $uploadOk = 1;
        } else {
            echo "<text>"."File is not an image."."</text>";
            return;
        }
    }
    // Check if file already exists
    //if (file_exists($target_file)) {
    //    echo "Sorry, file already exists.";
    //    $uploadOk = 0;
    //}
    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 100000000) {
        echo "<text>"."Sorry, your file is too large."."</text>";
        return;
    }
    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
        echo "<text>"."Sorry, only JPG, JPEG, PNG, GIF files are allowed."."</text>";
        return;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "<text>"."Sorry, your file was not uploaded."."</text>";
        return;
    // if everything is ok, try to upload file
    }
        // $img_width_height = getimagesize($target_file);
        // $img_width = $img_width_height[0];
        // $img_height = $img_width_height[1];

        // if($img_width > 1000 OR $img_height > 1000){
        //   $new_height = $img_width/$img_height;
        //   $width = 1000;
        //   $height = 1000/$new_height;
        //   if($img_width > 1000 OR $img_height > 1000){
        //       $new_height = $img_width/$img_height;
        //       $width = 900;
        //       $height = 900/$new_height;
        //   }

        //   if($imageFileType == "png"){
        //     resize_imagepng($target_file, $width, $height);
        //   }else if($imageFileType == "jpeg"){
        //     resize_imagejpeg($target_file, $width, $height); 
        //   }else if($imageFileType == "jpg"){
        //     resize_imagejpg($target_file, $width, $height); 
        //   }
           
        // }

    //upload image
    if (!move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        echo "<text>"."Sorry, there was an error uploading your file."."</text>";
        return;
    }

    $card_type = $_POST['card_type'];
    $card_type_id = -1;
    if ($card_type == 'PAN CARD INDIA')
        $card_type_id = 0;
    else if ($card_type == 'AADHAR CARD INDIA (Front)')
        $card_type_id = 1;
	else if ($card_type == 'AADHAR CARD INDIA (Back)')
        $card_type_id = 1;
	
    else if ($card_type == 'Passport & ID Card (MRZ)')
        $card_type_id = 2;
    else if ($card_type == 'INDIA PASSPORT (Back)')
    	$card_type_id = 6;

    //image load
    $image_string = file_get_contents($target_file);
    
    unlink($target_file);
    $dic = file_get_contents("db/mMQDF_f_Passport_bottom_Gray.dic");
    $dic1 = file_get_contents("db/mMQDF_f_Passport_bottom.dic");
    $trained_data = file_get_contents("db/eng.dat");
    $license = file_get_contents("db/key.license");
   // $fdata_path = realpath("db/fdata.nn");

    if (!extension_loaded("accuraMRZ"))
    {
        echo "<text>"."cardrec extension not loaded"."</text>";
        return;
    }
    
    gc_enable();

    $engine = new \Mrz($card_type_id);

    if ($license == null)
    {
        echo "<text>Could not find file './db/key.license' <br /> License Key Missing</text>";
        return;
    }
    
    $devinfo = $engine->getDevInfo();
   // $ret = $engine->loadDB($dic, $dic1, $trained_data, $license, $fdata_path);
    
	$ret = $engine->loadDB($dic, $dic1, $trained_data, $license);
	
	$strErr = $engine->getErrorMsg();

    if ($ret < 0)
    {
        //echo "<text>".$devinfo."<br />".$strErr."</text>";
        $resDev = json_decode($devinfo);
        $strHDD = $resDev->{'HDD'};
        $strDomain = $resDev->{'Domain'};
        if (strpos($strErr, 'Invalid') === false)
        	echo "<text>".$strErr."</text>";
        else
        	echo "<text>Your HDD Serial Key is ".$strHDD."<br />Your Domain is ".$strDomain."<br /><br />".$strErr."</text>";
        	
        return;
    }

    $image = imagecreatefromstring($image_string);
    

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

    if ($ret < 0)
    {
        //$result_face = $engine->getFaceImage(); //face image
        //$result_img = $engine->getCardImage();
        echo "<text>"."Failed to recognize"."</text>";
    	//echo "<face>"."data:image/jpg;base64,".base64_encode($result_face)."</face>";
    	//echo "<card>"."data:image/jpg;base64,".base64_encode($result_img)."</card>";
	echo "<face>"."data:image/jpeg;base64,".base64_encode(buildImage($engine->getFaceImage(), $engine->getFaceWidth(), $engine->getFaceHeight()))."</face>";
    	echo "<card>"."data:image/jpg;base64,".base64_encode(buildImage($engine->getCardImage(), $engine->getCardWidth(), $engine->getCardHeight()))."</card>";
        return;
    }
    //echo 'success to recognize';

    $result = $engine->getResult(); //recognition result string
    // print_r("<text>".$result."</text>");
    $order   = array("\r\n", "\n", "\r");
    $replace = '<br />';
    $result_rep = str_replace($order, $replace, $result);
    $res_obj = json_decode($result_rep);

    //echo "<text>".$result."</text>";
    //return;

    //var_dump($res_obj);
    /*
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - No errors';
            break;
        case JSON_ERROR_DEPTH:
            echo ' - Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            echo ' - Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            echo ' - Unknown error';
            break;
    }*/

    if ($card_type == "Passport & ID Card (MRZ)")
        $parse_text = parse_passport($res_obj, $ret);
    else if ($card_type == "PAN CARD INDIA")
        $parse_text = parse_pan($res_obj);
    else if ($card_type == "AADHAR CARD INDIA (Front)")
        $parse_text = parse_aadhar($res_obj);
    else if ($card_type == "AADHAR CARD INDIA (Back)")
        $parse_text = parse_aadhar($res_obj);
    else if ($card_type == 'INDIA PASSPORT (Back)')
    	$parse_text = parse_india_passport($res_obj);


    $result_list = explode("\n", $parse_text);
    $result_text ="";
    foreach($result_list as &$item){
        if ($item == "") continue;
        $result_text .= htmlspecialchars($item);
        $result_text .= "<br>";
    }

//    $result_face = $engine->getFaceImage(); //face image
//    $result_img = $engine->getCardImage(); //card image
//    echo "<face>"."data:image/jpg;base64,".base64_encode($result_face)."</face>";
//    echo "<card>"."data:image/jpg;base64,".base64_encode($result_img)."</card>";

	
    echo "<face>"."data:image/jpeg;base64,".base64_encode(buildImage($engine->getFaceImage(), $engine->getFaceWidth(), $engine->getFaceHeight()))."</face>";
    echo "<card>"."data:image/jpg;base64,".base64_encode(buildImage($engine->getCardImage(), $engine->getCardWidth(), $engine->getCardHeight()))."</card>";
    //echo "<text>".$devinfo."<br>".$result_text."</text>";
    //echo "<text>".$result_text."<br />".$strErr."</text>";
    echo "<text>".$result_text."</text>";
    
	$result_text = null;
	$result_face = null;
	$result_img = null;
return;
	$engine->freeEngine();
	$engine = null;

    function buildImage($colors, $width, $height)
    {
	$im = imagecreatetruecolor($width, $height);
	
	for ($y = 0; $y < $height; $y++) {
	    for ($x = 0; $x < $width; $x++) {
		$idx = ($y*$width + $x)*3;
		$colorItem = imagecolorallocate($im, ord(substr($colors, $idx+2, 1)), ord(substr($colors, $idx+1, 1)), ord(substr($colors, $idx, 1)));
		imagesetpixel($im, $x, $y, $colorItem);
	    }
	}

	ob_start(); 					// Let's start output buffering.
	    imagejpeg($im); 				//This will normally output the image, but because of ob_start(), it won't.
	    $contents = ob_get_contents(); 		//Instead, output above is saved to $contents
	ob_end_clean(); 				//End the output buffer.
	return $contents;
    }

    function parse_passport($arg, $flag)
    {
        $lines = $arg->{'Lines'};
        $doctype = $arg->{'DocType'};
        $country = $arg->{'Country'};
        $surname = $arg->{'Surname'};
        $givenname = $arg->{'Givename'};
        $docnumber = $arg->{'DocNumber'}; //Passport Number
        $passportchecksum = $arg->{'DocNumberCheckNumber'}; //Check Number
        $correctpassportnumber = $arg->{'CorrectDocNumberCheckNumber'}; //CorrectDocNumberCheckNumber
        $nationality = $arg->{'Nationality'};
        $birth = $arg->{'Birth'};
        $birthchecksum = $arg->{'BirthCheckNumber'};//Birth Check Number
        $correctbirthchecksum = $arg->{'CorrectBirthCheckNumber'};//CorrectBirth Check Number
        $sex = $arg->{'Sex'};
        $expirationdate = $arg->{'ExpirationDate'}; //Expiration Date
        $expirationchecksum = $arg->{'ExpirationCheckNumber'}; //Expiration Check Number
        $correctexpirationchecksum = $arg->{'CorrectExpirationCheckNumber'}; //CorrectExpiration Check Number
        $otherid = $arg->{'PersonalNumber'}; //Personal Number
        $otheridchecksum = $arg->{'PersonalCheckNumber'}; //Personal Number Check
        $correctotheridchecksum = $arg->{'CorrectPersonalCheckNumber'}; //CorrectPersonal Number Check
        $secondrowchecksum = $arg->{'SecondRowCheckNumber'}; //SecondRow Check Number

        $correctsecondrowchecksum = $arg->{'CorrectSecondRowCheckNumber'}; //CorrectSecondRow Check Number
    	$issuedate = $arg->{'IssueDate'};
    	$departmentnumber = $arg->{'DepartmentNumber'};

    	$incorrect_msg = [];
        if($passportchecksum != $correctpassportnumber){
            $incorrect_msg[] = "Incorrect Document Check Number";
            $flag = 0;
        }
        if($birthchecksum != $correctbirthchecksum){
            $incorrect_msg[]= "Incorrect Birth Check Number";
            $flag = 0;
        }
        if($expirationchecksum != $correctexpirationchecksum){
            $incorrect_msg[] = "Incorrect Expiry Check Number";
            $flag = 0;
        }
        if($secondrowchecksum != $correctsecondrowchecksum){
            $incorrect_msg[] = "Incorrect Second Row Check Number";
            $flag = 0;
        }

    	
        $mrz_result .= "MRZ : ".$lines."\n";
        $mrz_result .= "Document Type : ".$doctype."\n";
        $mrz_result .= "Country : ".$country."\n";
        $mrz_result .= "Last Name : ".$surname."\n";
        $mrz_result .= "First Names : ".$givenname."\n";
        $mrz_result .= "Document No: ".$docnumber."\n";
        $mrz_result .= "Document Check Number: ".$passportchecksum."\n";
        $mrz_result .= "Correct Document Check Number: ".$correctpassportnumber."\n";
        if($otherid != "" && $country == "ESP"){
            $mrz_result .= "dni: ".$otherid."\n";
        }
        $mrz_result .= "Nationality: ".$nationality."\n";
        $mrz_result .= "Date of Birth: ".$birth."\n";
        $mrz_result .= "Birth Check Number: ".$birthchecksum."\n";
        $mrz_result .= "Correct Birth Check Number: ".$correctbirthchecksum."\n";
        $mrz_result .= "Sex : ".$sex."\n";
        $mrz_result .= "Date of Expiry: ".$expirationdate."\n";
        $mrz_result .= "Expiry Check Number: ".$expirationchecksum."\n";

        $mrz_result .= "Correct Expiry Check Number: ".$correctexpirationchecksum."\n";
        if(strtoupper($country) =="RUS" or strtoupper($country) == "KAZ"){
            $mrz_result .= "Issue Date: ".$issuedate."\n";
            $mrz_result .= "Department Number : ".$departmentnumber."\n";
        }       	

        $mrz_result .= "Other ID : ".$otherid."\n";
        $mrz_result .= "Other ID Check: ".$otheridchecksum."\n";
        // $mrz_result .= "Correct Other ID Check: ".$correctotheridchecksum."\n";

        $mrz_result .= "Second Row Check Number: ".$secondrowchecksum."\n";
        $mrz_result .= "Correct Second Row Check Number: ".$correctsecondrowchecksum."\n";

        if ($flag == 0){
            $mrz_result .= "Result : Incorrect MRZ \n";
            // $mrz_result = "Incorrect Document \n";
        }else if($flag == 1){
            $mrz_result .= "Result : Correct MRZ \n";
            // $mrz_result = "Correct Document \n";
        }

        if($flag == 0){
           $mrz_result .=  "Details: ".implode(",",$incorrect_msg)."\n";
        }

	    $mrz_result .= "Flag: ".$flag;

        $order   = "<br />";
        $replace = "\n";
        return str_replace($order, $replace, $mrz_result);
    }

    function parse_pan($arg)
    {
        $cardtype = $arg->{'Card'};
        $name = $arg->{'Name'};
        $fathername = $arg->{'FatherName'};
        $birthday = $arg->{'Birthday'};
        $pan = $arg->{'PAN'};

        $pan_result  = "Card : ".$cardtype."\n";
        $pan_result .= "Name : ".$name."\n";
        $pan_result .= "Second Name : ".$fathername."\n";
        $pan_result .= "BOB : ".$birthday."\n";
        $pan_result .= "PAN Card No. : ".$pan;

        $order   = "<br />";
        $replace = "\n";
        return str_replace($order, $replace, $pan_result);
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

            $aad_result  = "Card : ".$cardtype."\n";
            $aad_result .= "Name : ".$name."\n";
            $aad_result .= "DOB : ".$birthday."\n";
            $aad_result .= "Sex : ".$sex."\n";
            $aad_result .= "Aadhar Card No. : ".$ann;

            $order   = "<br />";
            $replace = "\n";
            
            //echo $aad_result;
            $aad_result_val = str_replace($order, $replace, $aad_result);
            return $aad_result_val;
        }
        else if (strpos($cardtype, 'back') != false)
        {
            $address = $arg->{'Address'};

            $result  = "Card : ".$cardtype."\n";
            $result .= $address;

            $order   = "<br />";
            $replace = "\n";
            
            $aad_res_back = str_replace($order, $replace, $result);
            return $aad_res_back;
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

        $pass_result  = "Card : ".$cardtype."\n";
        $pass_result .= "Father Name : ".$fathername."\n";
        $pass_result .= "Mother Name : ".$mothername."\n";
        $pass_result .= "Name : ".$name."\n";
        $pass_result .= "Address : ".$address."\n";
        $pass_result .= "Passport No. : ".$passportno."\n";
        $pass_result .= "Date : ".$passdate."\n";
        $pass_result .= "Place Of Issue : ".$placeofissue."\n";
        $pass_result .= "File No. : ".$fileno;

        $order   = "<br />";
        $replace = "\n";
        return str_replace($order, $replace, $pass_result);
    }
?>