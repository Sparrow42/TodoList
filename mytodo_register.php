<pre>
<?php
print_r($_POST);
?>
</pre>

<?php
$dsn = 'mysql:dbname=todo_master;host=localhost;';
$user = 'root';
$pass = '';
$pdo = new PDO($dsn, $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

if($_POST){
    $needtime_est = $_POST['needhour_est']*60 + $_POST['needmin_est'];
    $sql = "INSERT INTO todo_master VALUES ('','".$_POST['title']."','".$_POST['shosai']."','".$_POST['start']."','".$_POST['target']."',
    '','".$needtime_est."','".$_POST['needhour_est']."','".$_POST['needmin_est']."','".$_POST['juyodo']."')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$non_input='0000-00-00 00:00:00';
$sql = "SELECT * FROM todo_master;";
$stmt = $pdo->query($sql);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    
    //推定所要時間の桁・単位の調整
    if($row['needhour_est']==0){
        $row['needhour_est']='';
    }else{
        $row['needhour_est'].='時間';
        if($row['needmin_est']==0){
            $row['needmin_est']='00';
        }
    }
    $row['needmin_est'].='分';

    //指定なし時刻非表示用
    if($row['start']==$non_input) $row['start']="";
    if($row['target']==$non_input) $row['target']="";
    if($row['finish']==$non_input) $row['finish']="";

    $result[]=$row;
}
?>

<html>
    <head>
        <link rel="stylesheet" type="text/css" href="css/table.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    </head>
    <h2>Todoリスト　新規登録フォーム</h2>
    <form method='post' action='http://localhost/line/mytodo_register.php'>
        Todo名：<input type='text' name='title'><br>
        詳細：<br>
        <textarea name='shosai' cols='40' rows='4'></textarea><br>
        <p>推定所要時間：
            <input type='number' name='needhour_est' min='0' max='10' value='0'>時間
            <input type='number' name='needmin_est' min='0' max='50' step='10' value='00'>分
        </p>
        <p>重要度：
            <input type='radio' name='juyodo' value='低'>低
            <input type='radio' name='juyodo' value='中' checked>中
            <input type='radio' name='juyodo' value='高'>高
        </p>
        開始日時：<input type='text' id='start' name='start'><br>
        終了予定日時：<input type='text' id='target' name='target'><br>
        <input type='submit' value='送信'><br>
        <a href='mytodo_menu.php'>マイメニューに戻る</a>
    </form>
    <table border="1">
            <tr>
                <th>行番号</th>
                <th>タイトル</th>
                <th>詳細</th>
                <th>推定所要時間</th>
                <th>重要度</th>
                <!--<th>開始日時</th>
                <th>終了予定日時</th>
                <th>終了日時</th>-->
            </tr>
            <?php foreach($result as $row){ ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><p class='disp'><?php echo $row['title']; ?></p></td>
                    <td><p class='disp'><?php echo $row['shosai']; ?></p></td>
                    <td><p class='disp'><?php echo $row['needhour_est'].$row['needmin_est']; ?></p></td>
                    <td><p class='disp'><?php echo $row['juyodo']; ?></p></td>
                    <!--<td><p class='disp'><?php /*echo $row['start']; ?></p></td>
                    <td><p class='disp'><?php echo $row['target']; ?></p></td>
                    <td><p class='disp'><?php echo $row['finish'];*/ ?></p></td>-->
                </tr>
            <?php } ?>
        </table>
</html>

<script>
    $(function(){
        var now = new Date();
        var y = now.getFullYear();
        var m = now.getMonth()+1;
        var d = now.getDate();
        var h = now.getHours();
        var mi = now.getMinutes();
        var mm = ('0' + m).slice(-2);
        var dd = ('0' + d).slice(-2);
        var hh = ('0' + h).slice(-2);
        var mmi = ('0' + mi).slice(-2);
        //$('#start').val(y + '/' + mm + '/' + dd + ' ' + hh + ':' + mmi);
        //$('#target').val(y + '/' + mm + '/' + dd + ' ' + hh + ':' + mmi);
        //$('#starttime').val(hh + ':' + mmi);
    });
</script>