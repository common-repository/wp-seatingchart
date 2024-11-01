<?php
$itemIcons = "";
$itemJSCreator = "";

$table = $wpdb->prefix . "wpsc_seatingchartitems";
$wpsc_seatingchart_table = $wpdb->prefix . "wpsc_seatingchart";
$seatingchartitems = $wpdb->get_results("SELECT * FROM $table", OBJECT);

$wpusers = $wpdb->get_results("SELECT ID, user_nicename FROM $wpdb->users ORDER BY user_nicename ASC");

foreach ($seatingchartitems as $seatingchartitem) {
    //
    //	add image dimensions to images
    //
	
	list($width, $height, $type, $attr) = getimagesize(WP_PLUGIN_DIR . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageN);
    $seatingchartitem->wpsc_ImageNWidth = $width;
    $seatingchartitem->wpsc_ImageNHeight = $height;

    $itemIcons .= "<a class='scItemSample' id='scItemSample_" . $seatingchartitem->wpsc_ItemTypeID . "' title='" . $seatingchartitem->wpsc_ItemTypeID . "' href='javascript:void(0);'>" . $seatingchartitem->wpsc_ItemName . "</a>";
    $itemIcons .= "<img id='scItemSample_" . $seatingchartitem->wpsc_ItemTypeID . "_Image' width='" . $width . "' height='" . $height . "' class='scItemSampleImage' src='" . WP_PLUGIN_URL . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageN . "' alt='" . $seatingchartitem->wpsc_ItemName . "' />";

    list($width, $height, $type, $attr) = getimagesize(WP_PLUGIN_DIR . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageE);
    $seatingchartitem->wpsc_ImageEWidth = $width;
    $seatingchartitem->wpsc_ImageEHeight = $height;

    list($width, $height, $type, $attr) = getimagesize(WP_PLUGIN_DIR . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageS);
    $seatingchartitem->wpsc_ImageSWidth = $width;
    $seatingchartitem->wpsc_ImageSHeight = $height;

    list($width, $height, $type, $attr) = getimagesize(WP_PLUGIN_DIR . "/wp-seatingchart/images/" . $seatingchartitem->wpsc_ImageW);
    $seatingchartitem->wpsc_ImageWWidth = $width;
    $seatingchartitem->wpsc_ImageWHeight = $height;
}

if (isset($_POST['update_wpscPluginSeriesSettings'])) {

    if (isset($_POST['wpsc_room_size_width'])) {
        $wpscOptions['wpsc_room_size_width'] = $_POST['wpsc_room_size_width'];
    }

    if (isset($_POST['wpsc_room_size_height'])) {
        $wpscOptions['wpsc_room_size_height'] = $_POST['wpsc_room_size_height'];
    }

    if (isset($_POST['wpsc_room_size_item_zoom'])) {
        $wpscOptions['wpsc_room_size_item_zoom'] = $_POST['wpsc_room_size_item_zoom'];
    }

    if (isset($_POST['wpsc_reservation_limit'])) {

        $reservationLimit = 1;
        if (intval($_POST['wpsc_reservation_limit'] . '') > 0)
        {
            $reservationLimit = intval($_POST['wpsc_reservation_limit'] . '');
        }

        $wpscOptions['wpsc_reservation_limit'] = $reservationLimit;
    }

    update_option($this->adminOptionsName, $wpscOptions);

    require_once(ABSPATH . "/wp-includes/js/tinymce/plugins/spellchecker/classes/utils/JSON.php");
    $json_obj = new Moxiecode_JSON();

    //echo stripslashes($_POST["wpsc_json"]) . "<br/>";

    $json = stripslashes($_POST["wpsc_json"]);
    $json_array = $json_obj->decode($json);

    //print_r($json_array);
    //echo "<br/>";
    //
	//	clear out old room details
    //
	
	$wpdb->query("DELETE FROM $wpsc_seatingchart_table");

    //
    //	cycle through all the items and update the database
    //
	
	if ($json_array["RoomItems"] != null) {
        foreach ($json_array["RoomItems"] as $key => $value) {
            //echo $key . ' ' . $value . '<br/>';
            //echo $value["ItemTypeID"];

            $sql = $wpdb->prepare("INSERT INTO $wpsc_seatingchart_table (wpsc_ItemTypeID, wpsc_Claimable, wpsc_ClaimedBy, wpsc_X, wpsc_Y, wpsc_Direction, wpsc_ZIndex) VALUES (%d, %d, %d, %d, %d, %s, %d)", $value["ItemTypeID"], $value["Claimable"], $value["ClaimedBy"], $value["Left"], $value["Top"], $value["Direction"], $value["ZIndex"]);


            //echo $sql;
            $wpdb->query($sql);
        }
    }
    ?>
    <div id="message" class="updated fade">
        Seating Chart has been <strong>updated</strong>.
    </div>
    <?php
}

$seatingchart = $wpdb->get_results("SELECT * FROM $wpsc_seatingchart_table", OBJECT);
?>

<script type="text/javascript">

    $j = jQuery.noConflict();

    var roomWidth = parseInt('<?php echo $wpscOptions['wpsc_room_size_width'] ?>');
    var roomHeight = parseInt('<?php echo $wpscOptions['wpsc_room_size_height'] ?>');
    var roomZoom = parseInt('<?php echo $wpscOptions['wpsc_room_size_item_zoom'] ?>');
    var room = null;

    var itemDetails = JSON.parse('<?php echo json_encode($seatingchartitems); ?>', null);
    var items = JSON.parse('<?php echo json_encode($seatingchart); ?>', null);
    var users = JSON.parse('<?php echo json_encode($wpusers); ?>', null);
    var selectedItem = null;
    var cache = [];

    function fadeUpdate()
    {
        $j('.fade').fadeOut(1000);
    }

    function addNewItemToRoom()
    {
        var wpsc_ItemTypeID = parseInt($j(this).attr("title"));
        var item = new RoomItem(wpsc_ItemTypeID, findJSONItem(wpsc_ItemTypeID).wpsc_Claimable, 0, 0, 0, "N", 1, 0);

        room.AddItem(item);
        room.Draw();
    }

    //
    //	Image Preloader: http://engineeredweb.com/blog/09/12/preloading-images-jquery-and-javascript
    //

    (function($) {
        // Arguments are image paths relative to the current page.
        $.preLoadImages = function() {
            var args_len = arguments.length;
            for (var i = args_len; i--; ) {
                var cacheImage = document.createElement('img');
                cacheImage.src = arguments[i];
                cache.push(cacheImage);
            }
        }
    })(jQuery);

    $j(document).ready(function()
    {
        room = new Room(roomWidth, roomHeight, roomZoom, $j("#divRoom"), true);

        //
        //	preload the item images
        //

        var imageD = '<?php echo WP_PLUGIN_URL; ?>/wp-seatingchart/images/';

        for (var i = 0; i < itemDetails.length; i++)
        {
            $j.preLoadImages(imageD + itemDetails[i].wpsc_ImageN, imageD + itemDetails[i].wpsc_ImageE, imageD + itemDetails[i].wpsc_ImageW, imageD + itemDetails[i].wpsc_ImageS);
        }

        //alert(cache[1].width + ' ' + cache[1].src);
        //
        //	populate with data from the database
        //

        for (var y = 0; y < items.length; y++)
        {
            var item = new RoomItem(parseInt(items[y]["wpsc_ItemTypeID"]), parseInt(items[y]["wpsc_Claimable"]), parseInt(items[y]["wpsc_ClaimedBy"]), parseInt(items[y]["wpsc_X"]), parseInt(items[y]["wpsc_Y"]), items[y]["wpsc_Direction"], items[y]["wpsc_ZIndex"], items[y]["wpsc_SeatingChartID"]);

            room.AddItem(item);
        }

        //
        //	show picture of item when hovered over the link
        //

        $j(".scItemSample").hover(
                function() {
                    $j("#" + $j(this).attr("id") + "_Image").show();
                }
        , function()
        {
            $j("#" + $j(this).attr("id") + "_Image").hide();
        }
        );

        $j("#sliderWidth").slider(
                {
                    min: 100,
                    max: 1000,
                    step: 5,
                    value: parseInt('<?php echo $wpscOptions['wpsc_room_size_width'] ?>'),
                    slide: function(event, ui)
                    {
                        $j("#wpsc_room_size_width").val(ui.value);
                        room.Width = ui.value;
                        room.Draw();
                    }

                }
        );

        $j("#sliderHeight").slider(
                {
                    min: 100,
                    max: 1000,
                    step: 5,
                    value: parseInt('<?php echo $wpscOptions['wpsc_room_size_height'] ?>'),
                    slide: function(event, ui)
                    {
                        $j("#wpsc_room_size_height").val(ui.value);
                        room.Height = ui.value;
                        room.Draw();
                    }
                }
        );

        $j("#sliderZoom").slider(
                {
                    min: 1,
                    max: 200,
                    value: parseInt('<?php echo $wpscOptions['wpsc_room_size_item_zoom']; ?>'),
                    slide: function(event, ui)
                    {
                        $j("#wpsc_room_size_item_zoom").val(ui.value);
                        room.Zoom = ui.value;
                        room.Draw();
                    }
                }
        );

        $j('#wpsc_form').submit(
                function()
                {
                    room.Save();
                    return true;
                }
        );

        $j("#wpsc_item_claimable").click(
                function()
                {
                    if ($j(this).attr('checked'))
                        selectedItem.Claimable = 1;
                    else
                        selectedItem.Claimable = 0;

                }
        );

        $j("#wpsc_item_claimedBy").change(
                function()
                {
                    selectedItem.ClaimedBy = $j(this).val();
                }
        );

        //
        //	add click event to item links to add item to room
        //

        $j(".scItemSample").click(addNewItemToRoom);

        room.Draw();

        //
        //	fade out the update message
        //

        setTimeout(fadeUpdate, 2000);

    });

</script>


<div class="wrap">
    <form id="wpsc_form" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
        <h2>Seating Chart Administration</h2>

        <div class="wpsc_inputItem">
            <h3>Room Width</h3>
            <input readonly type="text" id="wpsc_room_size_width" name="wpsc_room_size_width" value="<?php _e(apply_filters('format_to_edit', $wpscOptions['wpsc_room_size_width']), 'wp-seatingchart') ?>" />
            <div id="sliderWidth"> 
            </div>
        </div>

        <div class="wpsc_inputItem">
            <h3>Room Height</h3>
            <input readonly type="text" id="wpsc_room_size_height" name="wpsc_room_size_height" value="<?php _e(apply_filters('format_to_edit', $wpscOptions['wpsc_room_size_height']), 'wp-seatingchart') ?>" />
            <div id="sliderHeight"> 
            </div>
        </div>

        <div class="wpsc_inputItem">		
            <h3>Room Zoom</h3>
            <input readonly type="text" id="wpsc_room_size_item_zoom" name="wpsc_room_size_item_zoom" value="<?php _e(apply_filters('format_to_edit', $wpscOptions['wpsc_room_size_item_zoom']), 'wp-seatingchart') ?>" />
            <div id="sliderZoom"> 
            </div>
        </div>

        <div class="wpsc_inputItem">		
            <h3>Reservation Limit</h3>
            <input type="text" id="wpsc_reservation_limit" name="wpsc_reservation_limit" value="<?php _e(apply_filters('format_to_edit', $wpscOptions['wpsc_reservation_limit']), 'wp-seatingchart') ?>" />
        </div>

        <div class="wpsc_inputItem" id="divItemDetails">
            <h3>Item Details</h3>
            Claimable: 
            <input type="checkbox" id="wpsc_item_claimable" name="wpsc_item_claimable" />
            <br/>
            <div id="divClaimedBy">
                Claimed By:
                <select id="wpsc_item_claimedBy" name="wpsc_item_claimedBy">
                    <option value='0' >Unclaimed</option>
<?php
for ($i = 0; $i < count($wpusers); $i++) {
    echo "<option value='" . $wpusers[$i]->ID . "'>" . $wpusers[$i]->user_nicename . "</option>";
}
?>
                </select>
            </div>
        </div>

        <div class="clearBoth"></div>

        <div class="divIcons">
            <h3>Click to Add to Room - Doubleclick to Delete</h3>
                    <?php
                    echo $itemIcons;
                    ?>
        </div>

        <div id="divRoom">
        </div>

        <div class="submit">
            <input type="hidden" value="" id="wpsc_json" name="wpsc_json"></input>
            <input type="submit" id="update_wpscPluginSeriesSettings" name="update_wpscPluginSeriesSettings" value="<?php _e('Update Settings', 'wp-seatingchart') ?>" />
        </div>
    </form>
</div>