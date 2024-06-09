<?php
require_once(__DIR__ . '/../include/head.php');

if (isset($_REQUEST['op']) && $_REQUEST['op'] == 'create') {
    $stmt = $mysqli->prepare('INSERT INTO category (name) VALUES (?)');
    $stmt->bind_param("s", $_REQUEST['categoryName']);
    $stmt->execute();
}

$stmt = $mysqli->prepare('SELECT * FROM category');
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit video</title>
    <meta name="description" content="Small site to handle your YouTube channel.">
    <meta name="author" content="Daniel Persson">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/normalize.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/skeleton.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/custom.css?r=<?php echo $CSS_UPDATE ?>">   
</head>
<body>
    <div class="section hero">
        <div class="container">
            <div class="row">                
                <div class="one-half column">
                    <h3>Category</h3>
                    <div class="row">
                        <a class="button" href="listVideos.php">Home</a>
                        <a class="button" href="block.php">Block editor</a>
                    </div>
                    <div class="row">
                        <form action="" method="POST" name="categoryForm">
                            <input id="operationInput" type="hidden" name="op" value=""/>
                            <input type="text" name="categoryName" placeholder="Name" />
                            <button id="createButton">Create</button>
                        </form>
                    </div>
                </div>
                <div class="one-half column">
                    <table>
                        <tr>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                        <tr>                    
                            <?php
                            if (isset($data)) {
                                foreach($data as $category) {
                                    echo '<tr>';
                                    echo '<td>' . $category["name"] . '</td>';
                                    echo '<td>' . '</td>';
                                    echo '</tr>';
                                }    
                            }
                            ?>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const createButtonEl = document.getElementById('createButton');
        const operationEl = document.getElementById('operationInput');
        const categoryFormEl = document.getElementById('categoryForm');
        
        createButtonEl.addEventListener("click", function(e) {
            e.preventDefault();
            operationEl.value = "create";
            document.categoryForm.submit();            
        });
    </script>
</body>
</html>