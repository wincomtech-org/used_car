{__NOLAYOUT__}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>弹窗</title>
    <script type="text/javascript" src="__TMPL__/public/assets/js/jquery-1.11.3.min.js"></script>
    <script type="text/javascript" src="__TMPL__/public/assets/js/common.js"></script>
    <link rel="stylesheet" href="__TMPL__/public/assets/css/doubox.css"/>
</head>
<body>
<switch name="code">
    <case value="2">
        <!-- 底部显示 -->
        <div id="douBox">
            <link rel="stylesheet" href="__TMPL__/public/assets/css/doubox2.css"/>
            <div class="boxFrame">
                <div class="close_dou">x</div>
                <div class="boxCon">
                    <dl>
                        <dt class="boxdt">{$msg}</dt>
                        <dd class="boxdd1">当前媒介名：</dd>
                        <dd class="boxdd1">总数：</dd>
                        <dd class="boxdd1">价格总计：</dd>
                        <dd class="boxdd2"><a href="#">查看</a></dd>
                    </dl>
                </div>
            </div>
        </div>
    </case>
    <default/>
        <!-- 中央显示 -->
        <div id="douBox">
            <div class="boxBg"></div>
            <div class="boxFrame">
                <h2><a href="javascript:void(0)" class="close" onclick="douRemove('douBox')">X</a>提示</h2>
                <div class="boxCon">
                    <dt>{$msg}</dt>
                    <dd></dd>
                    <dd></dd>
                </div>
            </div>
        </div>
</switch>

</body>
</html>