<?php

/**
 *
 *@tail.php
 *@author: bruce
 *@version  v1.1.0
 *@since: v1.1.0
 *@date: 2015/7/8
 */
$date = date("y_m_d",time());
$file_name="./Runtime/Logs/$date.log";
$key = $date.":offset";
if (isset($_GET['ajax']) ) {
    if(!is_file($file_name)) exit;
    session_start();
    if(isset($_GET["reset"])&&$_GET["reset"]==1)  $_SESSION[$key] = 0;
    $handle = fopen($file_name, 'r');
    if (isset($_SESSION[$key])) {
        $data = stream_get_contents($handle, -1, $_SESSION[$key]);
        echo nl2br($data);
        fseek($handle, 0, SEEK_END);
        $_SESSION[$key] = ftell($handle);
    } else {
        $_SESSION[$key] = 0;
    }
    exit();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <script src="http://libs.baidu.com/jquery/2.0.0/jquery.min.js"></script>
    <script>
        /**
         * 是否重头载入
         */
        var is_reset = 0;
        /**
         * 设置sql语句运行时间警告阀值
         * @type {number}
         */
        var alert_runtime=0.1;
        $(function() {
            $("#filter").change(function(){
                $("#tail").html("");
            });
            tail();
            setInterval("tail()", 2000);
        });
        function tail(){
            var url = 'tail.php?ajax=1';
            if(is_reset) url+="&reset=1";
            is_reset = 0;
            $.get(url, function(data) {
                data = filter(data);
                var r = /(ERR|DEBUG|WARN|INFO|SQL):/g;
                data = data.replace(r,'<span class="$1">$1</span>:');
                var json_r = /({".+})/g;
                data = data.replace(json_r,parseJson);
                $('#tail').append(data);
            });
        }

        //filter key word
        function filter(str){
            var filter = $("#filter").val();
           if(filter==="") return str;
            var lines = str.split("\n"),reg = new  RegExp(filter+":");
            var reg_runtime = /\[\sRunTime:([\.0-9]+)s\s\]/;
            var reg_from = /^\[\s\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}\s\]/;
            _str="";
            for(var x in lines) {
                var line = lines[x];
                if (filter && !reg.test(line) && !reg_from.test(line)) {
                    continue;
                }
                if (reg_runtime.test(line)) {
                    var g = line.match(reg_runtime);
                    if (g[1] > alert_runtime)
                        line = line.replace(reg_runtime, "<span class='ERR'>" + g[0] + "</span>");
                }
                _str += line;
            }
            return _str;
        }

        //process unicode
        function hexToDec(str) {
            str=str.replace(/\\/g,"%");
            str = unescape(str);
            str = str.replace(/\%/g,"\\");
            return str;
        }
        //json process
        function parseJson(str){
            str = hexToDec(str);
            ret = syntaxHighlight(str);
            return "<pre>"+ret+"</pre>";
        }
        // highlight json string
        function syntaxHighlight(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                var cls = 'number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'key';
                    } else {
                        cls = 'string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'boolean';
                } else if (/null/.test(match)) {
                    cls = 'null';
                }
                return '<span class="' + cls + '">' + match + '</span><br>';
            });
        }

    </script>
    <style>
        html{font-size:100%;-webkit-text-size-adjust:none;}
        body{margin:0 auto;padding:10px;font-size:14px; max-width: 1880px; word-break: break-all;
            line-height: 20px;}
        pre {outline: 1px solid #ccc; padding: 5px; margin: 5px; }
        .string { color: green; }
        .number { color: darkorange; }
        .boolean { color: blue; }
        .null { color: magenta; }
        .key { color: red; }
        span{
            color:#FFFFFF;
        }
        span.DEBUG{
            background-color:#007aff;
        }
        span.ERR{
            background-color:#CB3C21;
        }
        span.WARN{
            background-color: #D9D957;
        }
        span.INFO{
            background-color:#1A7E1E;
        }
        span.SQL{
            background-color:#9e9e9e;
        }
    </style>
</head>
<body>
filter:
<select name="" id="filter">
    <option value="">ALL</option>
    <option value="ERR">ERR</option>
    <option value="DEBUG">DEBUG</option>
    <option value="WARN">WARN</option>
    <option value="INFO">INFO</option>
    <option value="SQL">SQL</option>
</select>  <button onclick="$('#tail').html('');window.is_reset=1">载入全部</button>
Starting up...Refresh to show newest log.
<div id="tail"></div>
</body>
</html>
