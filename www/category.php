<?php
require_once(__DIR__ . '/../include/head.php');

if (isset($_REQUEST['op']) && $_REQUEST['op'] == 'create') {
    $stmt = $mysqli->prepare('INSERT INTO category (name, userId) VALUES (?, ?)');
    $stmt->bind_param("si", $_REQUEST['categoryName'], $user['id']);
    $stmt->execute();
}

if (isset($_REQUEST['op']) && $_REQUEST['op'] == 'delete') {
    $stmt = $mysqli->prepare('DELETE FROM category WHERE id = ? AND userId = ?');
    $stmt->bind_param("ii", $_REQUEST['id'], $user['id']);
    $stmt->execute();
}

if (isset($_REQUEST['op']) && $_REQUEST['op'] == 'save') {
    $stmt = $mysqli->prepare('UPDATE category SET name = ? WHERE id = ? AND userId = ?');
    $stmt->bind_param("sii", $_REQUEST['categoryName'], $_REQUEST['categoryId'], $user['id']);
    $stmt->execute();
}

$currentCategoryId = '';
$currentCategoryName = '';
if (isset($_GET['categoryId'])) {
    $stmt = $mysqli->prepare('SELECT * FROM category WHERE id = ? AND userId = ?');
    $stmt->bind_param("ii", $_GET['categoryId'], $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $current = $result->fetch_assoc();
    if ($current !== false) {
        $currentCategoryId = $current['id'];
        $currentCategoryName = $current['name'];
    }
}

$stmt = $mysqli->prepare('SELECT * FROM category WHERE userId = ?');
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Categories</title>
    <meta name="description" content="Small site to handle your YouTube channel.">
    <meta name="author" content="Daniel Persson">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="css/normalize.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/skeleton.css?r=<?php echo $CSS_UPDATE ?>">
    <link rel="stylesheet" href="css/custom.css?r=<?php echo $CSS_UPDATE ?>">   
    <link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="images/favicon-16x16.png">
    <link rel="manifest" href="images/site.webmanifest">
</head>
<body>
    <div class="section hero">
        <div class="container">
            <?php require_once(__DIR__ . '/../include/topbar.php'); ?>

            <div class="row">                
                <div class="one-half column">
                    <div class="row">
                        <form action="" method="POST" name="categoryForm">
                            <input id="categoryId" type="hidden" name="categoryId" value="<?php echo $currentCategoryId ?>"/>
                            <input id="operationInput" type="hidden" name="op" value=""/>
                            <input type="text" name="categoryName" placeholder="Name" value="<?php echo $currentCategoryName ?>"/>
                        </form>
                    </div>
                    <div class="row">
                        <a href="?" class="button">Clear</a>
                        <button id="createButton">Create</button>
                        <button id="saveButton">Save</buttona>
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
                                    ?>
                                    <tr>
                                        <td><?php echo $category["name"] ?></td>
                                        <td>
                                            <a href="?categoryId=<?php echo $category["id"] ?>"><svg class="list-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg></a>&nbsp;
                                            <a href="?op=delete&id=<?php echo $category["id"] ?>"><svg class="list-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg></a>&nbsp;
                                        </td>
                                    </tr>
                                    <?php
                                }    
                            }
                            ?>
                        </tr>
                    </table>
                </div>
            </div>
            <?php require_once(__DIR__ . '/../include/footer.php'); ?>
        </div>
    </div>

    <script>
        const createButtonEl = document.getElementById('createButton');
        const saveButtonEl = document.getElementById('saveButton');
        const operationEl = document.getElementById('operationInput');
        const categoryFormEl = document.getElementById('categoryForm');
        
        createButtonEl.addEventListener("click", function(e) {
            e.preventDefault();
            operationEl.value = "create";
            document.categoryForm.submit();            
        });
        saveButtonEl.addEventListener("click", function(e) {
            e.preventDefault();
            operationEl.value = "save";
            document.categoryForm.submit();
        });
    </script>
</body>
</html>