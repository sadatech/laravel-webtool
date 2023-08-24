<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Webtool - Live Sync</title>
    <link rel="shortcut icon" href="https://cdn.sadata.id/logo-sadata.png" />
    <style>
        body {
            margin: 0;
            background-color:black;
            color:white;
        }

        iframe {
            display: block;
            background: #000;
            border: none;
            height: 100vh;
            width: 100vw;
        }
    </style>
</head>
<body>
    <div id="webtool-return-sync">
        <iframe src="{{ route('webtool.live-sync.action') }}?_token={{ csrf_token() }}" frameborder="0"></iframe>
    </div>

    <!-- <script type="text/javascript" src="https://code.jquery.com/jquery-3.7.0.min.js" integrity="sha256-2Pmvv0kuTBOenSvLm6bvfBSSHrUJ+3A7x6P5Ebd07/g=" crossorigin="anonymous"></script> -->
    <!-- <script type="text/javascript">
        $(document).ready(function(){
            $.ajax({
                url: "{{ route('webtool.live-sync.action') }}?_token={{ csrf_token() }}",
                async: true,
                type: "POST",
                contentType: false,
                processData: false,
                beforeSend: (function(){
                    $("#webtool-return-sync").html("Waiting response from backend server...");
                }),
                success: (function(return_msg){
                    $("#webtool-return-sync").html("");
                    $("#webtool-return-sync").append(return_msg);
                })
            });
        });
    </script> -->
</body>
</html>