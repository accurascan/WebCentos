<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/bootstrap.min.css" type = "text/css" />
    <script src="js/jquery-1.11.3.min.js"></script>
    <script src="js/common.js"></script>
    <title>Passport Scanner</title>
</head>
<body>

<div class="container">
    <div class="col-md-6">
        <h3>Passport Scanner</h3>
        <form method="POST" action="upload.php" enctype="multipart/form-data" target="_result">
            <!-- COMPONENT START -->
            <div class="form-group">
    			<div style="text-align: center;border:1px solid green; width: 100%; height: 300px;">
                	<img id="im_passport" name="im_passport" style="max-width: 100%; max-height: 100%;">
    			</div>
                <div class="input-group input-file" name="fileToUpload">
			        <span class="input-group-btn">
        		        <button class="btn btn-default btn-choose" type="button">Choose</button>
    		        </span>
                    <input type="text" class="form-control" placeholder='Choose a file...' />
    		        <span class="input-group-btn">
       			        <button class="btn btn-warning btn-reset" type="button">Reset</button>
    		        </span>
                </div>
            </div>
            <div class="form-group">
                <label for="card_type">Card Type:</label>
                <select class="form-control" id="card_type" name="card_type">
                 <option>Passport & ID Card (MRZ)</option>
				 <option>PAN CARD INDIA</option>
                 <option>AADHAR CARD INDIA (Front)</option>
				 <option>AADHAR CARD INDIA (Back)</option>
                 <option>INDIA PASSPORT (Back)</option>
                </select>
            </div>
            <!-- COMPONENT END -->
            <div class="form-group">
                <button type="submit" class="btn btn-primary pull-right">Recognize</button>
            </div>

        </form>
        <iframe id="_result" name="_result" style="visibility:hidden;height:0;"></iframe>
    </div>
    <div class="col-md-6" id="result_view" style="visibility: hidden;">
        <h3>Result</h3>
        <div id="image_view" style="visibility: hidden;">
        	<img id="im_face" name="im_face" style="max-width: 100%; max-height: 100%;">
        </div>
        <div id="result_content"></div>
        <div id="card_view" style="visibility: hidden;">
        	<img id="im_card" name="im_card" style="max-width: 100%; max-height: 100%;">
        </div>
    </div>
</div>
</body>
</html>


