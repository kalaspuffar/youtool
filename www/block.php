<?php
require_once(__DIR__ . '/../include/head.php');

$blockId = isset($_GET["blockId"]) ? $_GET["blockId"] : -1;

if(isset($_GET['op']) && $_GET['op'] == 'delete' && isset($_GET['id'])) {
    $stmt = $mysqli->prepare('DELETE FROM block WHERE id = ? AND userId = ?');
    $stmt->bind_param("ii", $_GET['id'], $user['id']);
    $stmt->execute();
}

$stmt = $mysqli->prepare('SELECT * FROM category WHERE userId = ?');
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$categories = fetchAssocAll($stmt, 'id');

$stmt = $mysqli->prepare(
    'SELECT b.*, GROUP_CONCAT(c.categoryId) as categories FROM '.
    'block as b LEFT JOIN category_to_block as c ON (b.id = c.blockId) ' . 
    'WHERE type NOT IN ("header", "footer") GROUP BY b.id'
);
$stmt->execute();
$result = $stmt->get_result();
$blocks = $result->fetch_all(MYSQLI_ASSOC);

$snippet = '';
$type = '';
if ($blockId != -1) {
    $stmt = $mysqli->prepare(
        'SELECT b.*, GROUP_CONCAT(c.categoryId) as categories FROM ' . 
        'block as b LEFT JOIN category_to_block as c ON (b.id = c.blockId) ' .
        'WHERE id = ?'
    );
    $stmt->bind_param("i", $blockId);
    $stmt->execute();
    $result = $stmt->get_result();    
    $data = $result->fetch_assoc();    
    $snippet = $data["snippet"];
    $type = $data["type"];
}


$types = [
    'social' => 'Socials',
    'ads' => 'Advertizement',
]

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Block editor</title>
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
            <h3>Block editor</h3>
            <div class="row">
                <a class="button" href="listVideos.php">List videos</a>
                <a class="button" href="category.php">Categories</a>
                <a class="button" href="comments.php">List comments</a>
            </div>

            <div class="row">
                <div class="one-half column">
                    <div class="u-full-width column">
                        <h4>Snippet: <?php echo $blockId ?></h4>
                        <input id="blockId" value="" type="hidden">
                        <textarea id="snippetText" rows="10" class="u-full-width"><?php echo $snippet ?></textarea>
                    </div>
                    <div class="row">
                        <div class="one-half column">
                            <label for="startTime">Start time:</label>
                            <input type="datetime-local" id="startTime" name="startTime" value="<?php echo $data['startTime'] ?>" />
                        </div>
                        <div class="one-half column">
                            <label for="endTime">End time:</label>
                            <input type="datetime-local" id="endTime" name="endTime" value="<?php echo $data['endTime'] ?>" />
                        </div>
                    </div>
                </div>
                <div class="one-half column">
                    <h4>Settings</h4>
                    <select name="type" id="blockType" class="u-full-width">
                        <option value="">Type</option>
                        <?php
                            foreach ($types as $typeKey => $typeName) {
                                $selected = $typeKey == $type ? 'selected' : '';
                                echo '<option ' . $selected . ' value="' . $typeKey . '">' . $typeName . '</option>';
                            }
                        ?>
                    </select>
                    <?php 
                        $categorySelectHeight = (count($categories) * 21) + 4;
                    ?>
                    <select name="category" id="blockCategory" class="u-full-width" multiple style="height: <?php echo $categorySelectHeight ?>px;">
                        <?php
                            $selectedCategories = explode(',', $data["categories"]);                           
                            foreach ($categories as $categoryKey => $category) {
                                $selected = in_array($categoryKey, $selectedCategories) ? 'selected' : '';
                                echo '<option ' . $selected . ' value="' . $categoryKey . '">' . $category["name"] . '</option>';
                            }
                        ?>
                    </select>

                    <label for="categoryOverride">
                        <?php
                            $categoryOverride = isset($data) && $data['override_categories'] ? 'checked' : '';
                        ?>
                        <input type="checkbox" id="categoryOverride" <?php echo $categoryOverride ?>>
                        <span class="label-body">Override categories</span>
                    </label>

                    <div class="row">
                        <a href="?" class="button">Clear</a>
                        <button id="createButton">Create</button>
                        <button id="saveButton">Save</buttona>
                    </div>
                </div>
            </div>
            <table class="u-full-width">
                <tr>
                    <th>Id</th>
                    <th>Snippet</th>
                    <th>Type</th>
                    <th>Categories</th>
                    <th>Start time</th>
                    <th>End time</th>
                    <th>Override categories</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            <?php
                foreach ($blocks as $block) {
                    $connectedCategories = [];
                    if (isset($block['categories'])) {
                        foreach (explode(',', $block['categories']) as $selectedCategory) {
                            array_push($connectedCategories, $categories[$selectedCategory]['name']);
                        }    
                    }
                    ?>
                    <tr>
                        <td><?php echo $block["id"] ?></td>
                        <td><?php echo substr($block["snippet"], 0, 15) ?></td>
                        <td><?php echo $types[$block["type"]] ?></td>
                        <td><?php echo implode(', ', $connectedCategories) ?></td>
                        <td><?php echo $block["startTime"] ?></td>
                        <td><?php echo $block["endTime"] ?></td>
                        <td><?php echo $block["override_categories"] ? 'true' : 'false' ?></td>
                        <td><?php echo $block["active"] ? 'true' : 'false' ?></td>
                        <td>
                            <a href="?op=delete&id=<?php echo $block["id"] ?>">X</a>&nbsp;
                            <a href="?blockId=<?php echo $block["id"] ?>">E</a>
                        </td>
                    </tr>
                    <?php                    
                }
            ?>
        </div>
    </div>

    <script>
        function makeDelay(ms) {
            var timer = 0;
            return function(callback){
                clearTimeout (timer);
                timer = setTimeout(callback, ms);
            };
        };
        var delay = makeDelay(400);

        const createButtonEl = document.getElementById('createButton');
        const saveButtonEl = document.getElementById('saveButton');

        const snippetTextEl = document.getElementById('snippetText');
        const blockTypeEl = document.getElementById('blockType');
        const blockCategoryEl = document.getElementById('blockCategory');

        const startTimeEl = document.getElementById('startTime');
        const endTimeEl = document.getElementById('endTime');
        const categoryOverrideEl = document.getElementById('categoryOverride');
        

        createButtonEl.addEventListener('click', function(e) { blockSave(-1) });
        saveButtonEl.addEventListener('click', function(e) { blockSave(<?php echo $blockId ?>) });

        function blockSave(blockId) {
            var blockCategoryOptions = blockCategoryEl.selectedOptions;
            var categoryValues = Array.from(blockCategoryOptions).map(({ value }) => value);
            const data = {
                'blockId': blockId,
                'type': blockTypeEl.value,
                'category': categoryValues,
                'startTime': startTimeEl.value,
                'endTime': endTimeEl.value,
                'categoryOverride': categoryOverrideEl.checked,
                'snippet': snippetTextEl.value
            }
            console.log(data);
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                location.href = '?blockId=' + body.id;
            })
        }
    </script>
</body>
</html>