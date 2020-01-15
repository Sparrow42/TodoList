<?php
$dsn = 'mysql:dbname=todo_master;host=localhost;';
$user = 'root';
$pass = '';
$pdo = new PDO($dsn, $user, $pass, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

if($_POST){
    $sql = "INSERT INTO todo_master VALUES ('','".$_POST['title']."','".$_POST['shosai']."','".$_POST['start']."','".$_POST['target']."','')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$sql = "SELECT * FROM todo_master;";
$stmt = $pdo->query($sql);
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
    $result[]=$row;
}

//指定なし時刻非表示用
$non_disp='0000-00-00 00:00:00';
$i=0;
foreach($result as $row){
    if($row['start']==$non_disp) $result[$i]['start']="";
    if($row['target']==$non_disp) $result[$i]['target']="";
    if($row['finish']==$non_disp) $result[$i]['finish']="";
    $i+=1;
}
$keys=array_keys($result[0]);

?>

<html>
    <head>
    <link rel="stylesheet" type="text/css" href="css/table.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    </head>
    <h2>Todoリスト　新規登録フォーム</h2>
    <form method='post' action='http://localhost/line/mytodo_register.php'>
        Todo名<input type='text' name='title'><br>
        詳細<input type='text' name='shosai'><br>
        開始日時<input type='text' id='start' name='start'><br>
        終了予定日時<input type='text' id='target' name='target'><br>
        <input type='submit' value='送信'><br>
        <a href='mytodo_menu.php'>マイメニューに戻る</a>
    </form>
    <table border="1">
            <tr>
                <th>行番号</th>
                <th>タイトル</th>
                <th>詳細</th>
                <th>開始日時</th>
                <th>終了予定日時</th>
                <th>終了日時</th>
            </tr>
            <?php foreach($result as $row){ ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <?php foreach($keys as $key => $value){ ?>
                        <?php if($key!=0){ ?>
                            <td><p id="<?php echo 'disp'.$row['id'].$value; ?>"><?php echo $row[$value]; ?></p></td>
                        <?php } ?>
                    <?php } ?>
                    <?php unset($value); ?>
                </tr>
            <?php } ?>
        </table>
</html>

<?php
/*$sql = "SELECT id,title,shosai,start,target,finish FROM todo_master;";
foreach ($pdo->query($sql) as $row) {
    print($row['id'].',');
    print($row['title'].',');
    print($row['shosai'].',');
    $non_disp='0000-00-00 00:00:00';
    if($row['start']!=$non_disp) print($row['start'].',');
    else echo "指定なし,";
    if($row['target']!=$non_disp) print($row['target'].',');
    else echo "指定なし,";
    if($row['finish']!=$non_disp) print($row['finish']);
    else echo "指定なし";
    print('<br />');
}*/
?>

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
        $('#start').val(y + '/' + mm + '/' + dd + ' ' + hh + ':' + mmi);
        $('#target').val(y + '/' + mm + '/' + dd + ' ' + hh + ':' + mmi);
        //$('#starttime').val(hh + ':' + mmi);
    });
</script>