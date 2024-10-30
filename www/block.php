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

$extraQuery = '';
if (isseT($_GET['active'])) {
    $extraQuery = ' AND active = true ';
}

$stmt = $mysqli->prepare(
    'SELECT b.*, GROUP_CONCAT(c.categoryId) as categories FROM '.
    'block as b LEFT JOIN category_to_block as c ON (b.id = c.blockId) ' . 
    'WHERE type NOT IN ("header", "footer") ' .$extraQuery. ' AND userId = ? GROUP BY b.id'
);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$blocks = $result->fetch_all(MYSQLI_ASSOC);

$snippetName = '';
$snippet = '';
$type = '';
$adsId = -1;
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
    $snippetName = $data["name"];
    $snippet = $data["snippet"];
    $type = $data["type"];
    $adsId = $data["adsId"];
}


$types = [
    'social' => 'Socials',
    'ads' => 'Advertizement',
];

$startTime = isset($data['startTime']) ? $data['startTime'] : '';
$endTime = isset($data['endTime']) ? $data['endTime'] : '';

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
            <?php require_once(__DIR__ . '/../include/topbar.php'); ?>

            <div class="row">
                <div class="one-half column">
                    <div class="u-full-width column">
                        <h4>Snippet: <?php echo $blockId ?></h4>
                        <input id="blockId" value="" type="hidden">
                        <input id="snippetName"  type="text" placeholder="Name" value="<?php echo $snippetName ?>" class="u-full-width">
                        <textarea id="snippetText" rows="10" class="u-full-width"><?php echo $snippet ?></textarea>
                    </div>
                    <div class="row">
                        <div class="one-half column">
                            <label for="startTime">Start time:</label>
                            <input type="datetime-local" id="startTime" name="startTime" value="<?php echo $startTime ?>" />
                        </div>
                        <div class="one-half column">
                            <label for="endTime">End time:</label>
                            <input type="datetime-local" id="endTime" name="endTime" value="<?php echo $endTime ?>" />
                        </div>
                    </div>
                    <?php if ($user['id'] == 1) { ?>
                    <div class="row">
                        <select name="adsId" id="adsId" class="u-full-width">
                            <option value="-1">None</option>
                        <?php
                            $stmt = $mysqli->prepare('SELECT * FROM ads ORDER BY startTime DESC');
                            $stmt->execute();
                            $result = $stmt->get_result();

                            $items = $result->fetch_all(MYSQLI_ASSOC);
                            foreach ($items as $ad) {

                                $selected_ad = $ad['id'] == $adsId ? 'selected' : '';
                        ?>
                            <option <?php echo $selected_ad ?> value="<?php echo $ad['id'] ?>"><?php echo $ad['advertiserName'] . ' - '. $ad['name'] ?></option>
                        <?php } ?>
                        </select>

                        <button id="updateAdsTimes">Update Times</button>

                        <div id="adsData">
                        </div>
                    </div>
                    <?php } ?>
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
                            $selectedCategories = [];
                            if (isset($data["categories"])) {
                                $selectedCategories = explode(',', $data["categories"]);
                            }
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

            <label for="showOnlyActive">
                <?php
                    $showOnlyActive = isset($_GET['active']) ? 'checked' : '';
                ?>
                <input type="checkbox" id="showOnlyActive" <?php echo $showOnlyActive ?>>
                <span class="label-body">Show only active</span>
            </label>

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
                        <td><?php echo $block["name"] ?></td>
                        <td><?php echo $types[$block["type"]] ?></td>
                        <td><?php echo implode(', ', $connectedCategories) ?></td>
                        <td><?php echo $block["startTime"] ?></td>
                        <td><?php echo $block["endTime"] ?></td>
                        <td><?php echo $block["override_categories"] ? 'true' : 'false' ?></td>
                        <td><?php echo $block["active"] ? 'true' : 'false' ?></td>
                        <td>
                            <a href="?blockId=<?php echo $block["id"] ?>"><svg class="list-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M471.6 21.7c-21.9-21.9-57.3-21.9-79.2 0L362.3 51.7l97.9 97.9 30.1-30.1c21.9-21.9 21.9-57.3 0-79.2L471.6 21.7zm-299.2 220c-6.1 6.1-10.8 13.6-13.5 21.9l-29.6 88.8c-2.9 8.6-.6 18.1 5.8 24.6s15.9 8.7 24.6 5.8l88.8-29.6c8.2-2.7 15.7-7.4 21.9-13.5L437.7 172.3 339.7 74.3 172.4 241.7zM96 64C43 64 0 107 0 160V416c0 53 43 96 96 96H352c53 0 96-43 96-96V320c0-17.7-14.3-32-32-32s-32 14.3-32 32v96c0 17.7-14.3 32-32 32H96c-17.7 0-32-14.3-32-32V160c0-17.7 14.3-32 32-32h96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96z"/></svg></a>&nbsp;
                            <a href="?op=delete&id=<?php echo $block["id"] ?>"><svg class="list-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M135.2 17.7L128 32H32C14.3 32 0 46.3 0 64S14.3 96 32 96H416c17.7 0 32-14.3 32-32s-14.3-32-32-32H320l-7.2-14.3C307.4 6.8 296.3 0 284.2 0H163.8c-12.1 0-23.2 6.8-28.6 17.7zM416 128H32L53.2 467c1.6 25.3 22.6 45 47.9 45H346.9c25.3 0 46.3-19.7 47.9-45L416 128z"/></svg></a>&nbsp;
                        </td>
                    </tr>
                    <?php                    
                }
            ?>
            </table>
            <?php require_once(__DIR__ . '/../include/footer.php'); ?>
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
        const snippetNameEl = document.getElementById('snippetName');
        
        const blockTypeEl = document.getElementById('blockType');
        const blockCategoryEl = document.getElementById('blockCategory');
        const adsIdEl = document.getElementById('adsId');

        const startTimeEl = document.getElementById('startTime');
        const endTimeEl = document.getElementById('endTime');
        const categoryOverrideEl = document.getElementById('categoryOverride');
        const showOnlyActiveEl = document.getElementById('showOnlyActive');
        const adsDataEl = document.getElementById('adsData');
        const updateAdsTimesEl  = document.getElementById('updateAdsTimes');
                
        createButtonEl.addEventListener('click', function(e) { blockSave(-1) });
        saveButtonEl.addEventListener('click', function(e) { blockSave(<?php echo $blockId ?>) });
        showOnlyActiveEl.addEventListener('click', function(e) { reloadActive(); });
        adsIdEl.addEventListener('change', function(e) { updateAdsData(e.target.value); });
        updateAdsTimesEl.addEventListener('click', function(e) { updateAdTimes(<?php echo $blockId ?>); });

        function reloadActive() {
            location.href = showOnlyActiveEl.checked ? '?active=true' : '?';
        }

        function blockSave(blockId) {
            var blockCategoryOptions = blockCategoryEl.selectedOptions;
            var categoryValues = Array.from(blockCategoryOptions).map(({ value }) => value);
            const data = {
                'blockId': blockId,
                'adsId': adsIdEl.value,
                'type': blockTypeEl.value,
                'category': categoryValues,
                'startTime': startTimeEl.value,
                'endTime': endTimeEl.value,
                'categoryOverride': categoryOverrideEl.checked,
                'snippet': snippetTextEl.value,
                'snippetName': snippetNameEl.value
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.json()
            ).then((body) => {
                location.href = '?blockId=' + body.id;
            })
        }

        function updateAdsData(adsId) {
            var blockCategoryOptions = blockCategoryEl.selectedOptions;
            var categoryValues = Array.from(blockCategoryOptions).map(({ value }) => value);
            const data = {
                'op': 'fetchAdData',
                'adsId': adsId
            }
            fetch("backAjax.php", {
                method: 'POST',
                body: JSON.stringify(data)
            }).then((res) => res.text()
            ).then((body) => {
                adsDataEl.innerHTML = body;
            })
        }
        updateAdsData(<?php echo $adsId ?>);

        function updateAdTimes(blockId) {
            const data = {
                'op': 'updateAdTimes',
                'blockId': blockId
            }
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