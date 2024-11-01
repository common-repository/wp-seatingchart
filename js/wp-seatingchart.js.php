<?php
//wp-seatingchart.js.php

if (!function_exists('add_action')) {
    require_once("../../../../wp-config.php");
}

global $current_user;
get_currentuserinfo();
?>

<?php if (false): ?>
    <script type="text/javascript">
<?php endif; ?>
    var imageDir = '<?php echo WP_PLUGIN_URL; ?>/wp-seatingchart/images/';
    var ajaxURL = '<?php echo WP_PLUGIN_URL; ?>/wp-seatingchart/wp-seatingchart.php';
    var nextIndex = 0;
    var directionsRight = {N: 'E', E: 'S', S: 'W', W: 'N'};
    var directionsLeft = {N: 'W', W: 'S', S: 'E', E: 'N'};
    var currentUserId = parseInt('<?php echo $current_user->ID; ?>');

    function findJSONItem(itemTypeID)
    {
        for (var i = 0; i < itemDetails.length; i++)
        {

            if (parseInt(itemDetails[i].wpsc_ItemTypeID) == parseInt(itemTypeID))
                return itemDetails[i];
        }
        return null;
    }

    function findJSONUser(userID)
    {
        for (var i = 0; i < users.length; i++)
        {
            if (parseInt(users[i].ID) == parseInt(userID))
                return users[i];
        }
        return null;
    }


    function NextDirection(direction, way)
    {
        if (way == 'RIGHT')
        {
            return directionsRight[direction];
        }
        if (way == 'LEFT')
        {
            return directionsLeft[direction];
        }
    }

    function RoomItem(g_itemTypeID, g_claimable, g_claimedBy, g_x, g_y, g_direction, g_zIndex, g_seatingChartID)
    {
        this.id = "wpsc_i_" + (nextIndex++);
        this.ItemTypeID = g_itemTypeID;
        this.Claimable = g_claimable;
        this.ClaimedBy = g_claimedBy;

        this.Direction = g_direction;
        this.OrigWidth = null;
        this.OrigHeight = null;
        this.Container = null;
        this.Image = null;
        this.ZIndex = g_zIndex;
        this.Top = g_y;
        this.Left = g_x;

        this.SeatingChartID = g_seatingChartID;

        this.UpdateUserOverlays = function(canvas, zoom, admin)
        {

            var claimedBy = $j('#' + this.id + '_claimedBy');

            if (room.RoomItems[this.id].Claimable != 0)
            {
                //this piece is not claimable

                if (room.RoomItems[this.id].ClaimedBy != 0)
                {
                    //this piece is potentially claimed

                    var user = findJSONUser(room.RoomItems[this.id].ClaimedBy);

                    if (user != null)
                    {
                        claimedBy.attr('src', 'http://www.gravatar.com/avatar/' + user.gravatar_hash + '?s=' + ((this.OrigWidth * (zoom / 100)) / 2) + "&d=" + escape(imageDir) + "reserved.png");
                        //alert((this.OrigWidth * (zoom / 100)) + ' ' + ((this.OrigWidth * (zoom / 100))/2));
                        claimedBy.css('left', ((this.OrigWidth * (zoom / 100)) - ((this.OrigWidth * (zoom / 100)) / 2)) / 2);
                        claimedBy.css('top', ((this.OrigHeight * (zoom / 100)) - ((this.OrigHeight * (zoom / 100)) / 2)) / 2);
                        claimedBy.css('width', ((this.OrigWidth * (zoom / 100)) / 2));
                        claimedBy.css('height', ((this.OrigWidth * (zoom / 100)) / 2));
                        //claimedBy.css('opacity', '0.5');
                        claimedBy.fadeIn(1000);

                        //$j(this.Container).css("opacity", "0.7");
                        $j(this.Container).tooltip(
                                {
                                    track: true,
                                    bodyHandler:
                                            function()
                                            {
                                                return 'reserved by ' + user.user_nicename;
                                            }
                                }
                        );
                    }
                    else
                    {
                        claimedBy.hide();
                        $j(this.Container).tooltip(
                                {
                                    track: true,
                                    bodyHandler:
                                            function()
                                            {
                                                return 'unreserved';
                                            }
                                }
                        );

                    }
                }
                else
                {
                    claimedBy.hide();
                    $j(this.Container).tooltip(
                            {
                                track: true,
                                bodyHandler:
                                        function()
                                        {
                                            return 'unreserved';
                                        }
                            }
                    );
                }
            }
        }

        this.Draw = function(canvas, zoom, admin)
        {

            //
            //	see if it is already drawn
            //

            if ($j("#" + this.id).length > 0)
            {

                if (admin)
                {
                    if (this.Direction == 'N' || this.Direction == 'S')
                    {
                        this.Image.width(this.OrigWidth * (zoom / 100));
                        this.Image.height(this.OrigHeight * (zoom / 100));
                    }
                    else
                    {
                        this.Image.height(this.OrigWidth * (zoom / 100));
                        this.Image.width(this.OrigHeight * (zoom / 100));
                    }

                    this.Image.attr("src", imageDir + findJSONItem(this.ItemTypeID)["wpsc_Image" + this.Direction]);
                    this.Container.css("z-index", this.ZIndex);
                }
                else
                {
                    this.UpdateUserOverlays(canvas, zoom, admin);
                }
            }
            else
            {

                canvas.append("<div id='" + this.id + "' class='scRoomItemContainer'><img id='" + this.id + "_claimedBy' class='scRoomItemImageClaimedBy' src='' /><img id='" + this.id + "_image' class='scRoomItemImage' src='" + imageDir + findJSONItem(this.ItemTypeID)["wpsc_Image" + this.Direction] + "'/></div>");

                //
                //	adjust for theimage being horizontal
                //

                if (this.Direction == 'N' || this.Direction == 'S')
                {
                    this.OrigWidth = findJSONItem(this.ItemTypeID)["wpsc_Image" + this.Direction + "Width"];//document.getElementById(this.id + '_image').width;//$j('#' + this.id + "_image").width();
                    this.OrigHeight = findJSONItem(this.ItemTypeID)["wpsc_Image" + this.Direction + "Height"];//document.getElementById(this.id + '_image').height;//$j('#' + this.id + "_image").height();
                }
                else
                {
                    this.OrigWidth = findJSONItem(this.ItemTypeID)["wpsc_Image" + this.Direction + "Height"];//document.getElementById(this.id + '_image').height;//$j('#' + this.id + "_image").height();
                    this.OrigHeight = findJSONItem(this.ItemTypeID)["wpsc_Image" + this.Direction + "Width"];//document.getElementById(this.id + '_image').width;//$j('#' + this.id + "_image").width();
                }

                this.Container = $j('#' + this.id);
                this.Image = $j('#' + this.id + "_image");
                this.Image.toJSON = function() {
                    return"";
                };

                this.Container.css('position', 'absolute');
                this.Container.css('top', this.Top);
                this.Container.css('left', this.Left);
                this.Container.css("z-index", this.ZIndex);

                this.Container.toJSON = function() {
                    return"";
                };

                if (this.Direction == 'N' || this.Direction == 'S')
                {
                    this.Image.width(this.OrigWidth * (zoom / 100));
                    this.Image.height(this.OrigHeight * (zoom / 100));
                }
                else
                {
                    this.Image.height(this.OrigWidth * (zoom / 100));
                    this.Image.width(this.OrigHeight * (zoom / 100));
                }

                this.Image.css("z-index", 1);

                //
                //	administrative functions
                //

                if (admin)
                {

                    this.Image.dblclick(
                            function()
                            {
                                var itemToDelete = room.RoomItems[$j(this).parent().attr("id")];
                                itemToDelete.Container.remove();
                                itemToDelete = null;
                                delete room.RoomItems[$j(this).parent().attr("id")];
                            }
                    );

                    //
                    //	add rotate overlay
                    //

                    this.Container.hover(
                            function()
                            {

                                selectedItem = room.RoomItems[$j(this).attr("id")];
                                room.RoomItems[$j(this).attr("id")].Image.css("opacity", "0.7");

                                $j(this).append("<img id='" + $j(this).attr("id") + "_rotateLeft' src='" + imageDir + "rotateleft.png' class='iconRotate'/>");
                                $j("#" + $j(this).attr("id") + "_rotateLeft").css("left", 0);
                                $j("#" + $j(this).attr("id") + "_rotateLeft").css("top", 0);
                                $j("#" + $j(this).attr("id") + "_rotateLeft").show();//fadeIn(300);
                                $j("#" + $j(this).attr("id") + "_rotateLeft").click(
                                        function()
                                        {
                                            room.RoomItems[$j(this).parent().attr("id")].Direction = NextDirection(room.RoomItems[$j(this).parent().attr("id")].Direction, 'LEFT');
                                            room.Draw();
                                        }
                                );

                                $j(this).append("<img id='" + $j(this).attr("id") + "_rotateRight' src='" + imageDir + "rotateright.png' class='iconRotate'/>");
                                $j("#" + $j(this).attr("id") + "_rotateRight").css("right", 0);
                                $j("#" + $j(this).attr("id") + "_rotateRight").css("top", 0);
                                $j("#" + $j(this).attr("id") + "_rotateRight").show();//fadeIn(300);
                                $j("#" + $j(this).attr("id") + "_rotateRight").click(
                                        function()
                                        {
                                            room.RoomItems[$j(this).parent().attr("id")].Direction = NextDirection(room.RoomItems[$j(this).parent().attr("id")].Direction, 'RIGHT');
                                            room.Draw();
                                        }
                                );

                                $j(this).append("<img id='" + $j(this).attr("id") + "_bringForward' src='" + imageDir + "bringforward.png' class='iconRotate'/>");
                                $j("#" + $j(this).attr("id") + "_bringForward").css("left", 0);
                                $j("#" + $j(this).attr("id") + "_bringForward").css("bottom", 0);
                                $j("#" + $j(this).attr("id") + "_bringForward").show();//fadeIn(300);
                                $j("#" + $j(this).attr("id") + "_bringForward").click(
                                        function()
                                        {
                                            room.RoomItems[$j(this).parent().attr("id")].ZIndex++;
                                            room.Draw();
                                        }
                                );


                                $j(this).append("<img id='" + $j(this).attr("id") + "_sendBack' src='" + imageDir + "sendback.png' class='iconRotate'/>");
                                $j("#" + $j(this).attr("id") + "_sendBack").css("right", 0);
                                $j("#" + $j(this).attr("id") + "_sendBack").css("bottom", 0);
                                $j("#" + $j(this).attr("id") + "_sendBack").show();//fadeIn(300);
                                $j("#" + $j(this).attr("id") + "_sendBack").click(
                                        function()
                                        {
                                            if (room.RoomItems[$j(this).parent().attr("id")].ZIndex > 1)
                                                room.RoomItems[$j(this).parent().attr("id")].ZIndex--;
                                            room.Draw();
                                        }
                                );


                                $j("#divItemDetails").show();

                                //
                                //	set the claimable checkbox
                                //

                                //see if the object is claimable at all
                                if (findJSONItem(room.RoomItems[$j(this).attr("id")].ItemTypeID)["wpsc_Claimable"] == 0)
                                {
                                    $j("#wpsc_item_claimable").attr("checked", false);
                                    $j("#wpsc_item_claimable").attr("disabled", true);
                                    $j("#divClaimedBy").hide();
                                }
                                else
                                {
                                    $j("#wpsc_item_claimable").attr("disabled", false);
                                    $j("#divClaimedBy").show();

                                    //set the checkbox
                                    if (room.RoomItems[$j(this).attr("id")].Claimable == 1)
                                    {
                                        $j("#wpsc_item_claimable").attr("checked", true);
                                    }
                                    else
                                    {
                                        $j("#wpsc_item_claimable").attr("checked", false);
                                    }

                                    //set the select
                                    $j('#wpsc_item_claimedBy').attr('selectedIndex', '-1');
                                    $j("#wpsc_item_claimedBy option[value='" + room.RoomItems[$j(this).attr("id")].ClaimedBy + "']").attr('selected', 'selected');


                                }

                            }
                    ,
                            function()
                            {
                                $j(".iconRotate").remove();//fadeOut(300,function(){$j(this).remove()});
                                room.RoomItems[$j(this).attr("id")].Image.css("opacity", "1.0");


                            }
                    );
                }
                else
                {

                    //
                    //	add user overlay
                    //

                    this.UpdateUserOverlays(canvas, zoom, admin);

                    //skip hover if the user is not signed in
                    if (currentUserId > 0)
                    {
                        this.Container.hover(
                                function()
                                {

                                    //see if the object is claimable at all
                                    if (findJSONItem(room.RoomItems[$j(this).attr("id")].ItemTypeID)["wpsc_Claimable"] == 0 || room.RoomItems[$j(this).attr("id")].Claimable == 0)
                                    {
                                        //alert('unclaimable');
                                    }
                                    else
                                    {

                                        //
                                        //	not claimed
                                        //

                                        if (room.RoomItems[$j(this).attr("id")].ClaimedBy == 0 && room.ReservationCount(currentUserId) < reservationLimit)
                                        {
                                            //$j(this).append("<a href='javascript:void(0);' id='" + $j(this).attr("id") + "_sitDown' class='linkSitDown'>Sit Down</a>");
                                            $j(this).append("<img id='" + $j(this).attr("id") + "_sitDown' src='" + imageDir + "sitdown.png' class='linkSitDown' alt='Sit Down'/>");

                                            //
                                            //	get optimal placement for message
                                            //

                                            var tHeight = room.RoomItems[$j(this).attr("id")].OrigHeight * (zoom / 100);
                                            var tWidth = room.RoomItems[$j(this).attr("id")].OrigWidth * (zoom / 100);

                                            tHeight = ((tHeight / 2) - 7);
                                            tHeight = tHeight < 0 ? 0 : tHeight;
                                            tWidth = ((tWidth / 2) - 28);
                                            tWidth = tWidth < 0 ? 0 : tWidth;

                                            $j("#" + $j(this).attr("id") + "_sitDown").css("left", tWidth);
                                            $j("#" + $j(this).attr("id") + "_sitDown").css("top", tHeight);

                                            $j("#" + $j(this).attr("id") + "_sitDown").click(
                                                    function()
                                                    {
                                                        //alert(ajaxURL + ' ' + room.RoomItems[$j(this).parent().attr("id")].SeatingChartID);
                                                        var rItem = room.RoomItems[$j(this).parent().attr("id")];
                                                        var scid = rItem.SeatingChartID;
                                                        $j('#divAjaxLoader').show();
                                                        $j.post(ajaxURL, {ajax: "true", command: "SitDown", wpsc_SeatingChartID: scid},
                                                        function(data)
                                                        {
                                                            if (data == "OK")
                                                            {
                                                                rItem.ClaimedBy = currentUserId;
                                                            }
                                                            else
                                                            {
                                                                alert(data);
                                                            }
                                                            $j('#divAjaxLoader').fadeOut(500);
                                                            room.Draw();

                                                        }
                                                        );
                                                    }
                                            );

                                        }

                                        //
                                        //	claimed by me
                                        //

                                        if (room.RoomItems[$j(this).attr("id")].ClaimedBy == currentUserId)
                                        {
                                            //$j(this).append("<a href='javascript:void(0);' id='" + $j(this).attr("id") + "_standUp' class='linkStandUp'>Stand Up</a>");
                                            $j(this).append("<img id='" + $j(this).attr("id") + "_standUp' src='" + imageDir + "standup.png' class='linkStandUp' alt='Stand Up'/>");


                                            var tHeight = room.RoomItems[$j(this).attr("id")].OrigHeight * (zoom / 100);
                                            var tWidth = room.RoomItems[$j(this).attr("id")].OrigWidth * (zoom / 100);

                                            tHeight = ((tHeight / 2) - 7);
                                            tHeight = tHeight < 0 ? 0 : tHeight;
                                            tWidth = ((tWidth / 2) - 28);
                                            tWidth = tWidth < 0 ? 0 : tWidth;


                                            $j("#" + $j(this).attr("id") + "_standUp").css("left", tWidth);
                                            $j("#" + $j(this).attr("id") + "_standUp").css("top", tHeight);

                                            $j("#" + $j(this).attr("id") + "_standUp").click(
                                                    function()
                                                    {
                                                        //alert(ajaxURL + ' ' + room.RoomItems[$j(this).parent().attr("id")].SeatingChartID);
                                                        var rItem = room.RoomItems[$j(this).parent().attr("id")];
                                                        var scid = rItem.SeatingChartID;
                                                        $j('#divAjaxLoader').show();
                                                        $j.post(ajaxURL, {ajax: "true", command: "StandUp", wpsc_SeatingChartID: scid},
                                                        function(data)
                                                        {
                                                            if (data == "OK")
                                                            {
                                                                rItem.ClaimedBy = 0;
                                                            }
                                                            else
                                                            {
                                                                alert(data);
                                                            }
                                                            $j('#divAjaxLoader').fadeOut(500);
                                                            room.Draw();
                                                        }
                                                        );
                                                    }
                                            );
                                        }

                                    }
                                }
                        ,
                                function()
                                {
                                    $j(".linkSitDown").remove();
                                    $j(".linkStandUp").remove();
                                }
                        );
                    }
                }
            }
        };
    }

    function Room(g_width, g_height, g_zoom, canvas, admin)
    {
        this.Width = g_width;
        this.Height = g_height;
        this.Zoom = g_zoom;
        this.RoomItems = {};
        this.Canvas = canvas;
        this.Canvas.toJSON = function() {
            return"";
        };
        this.Admin = admin;

        this.AddItem = function(item)
        {
            this.RoomItems[item.id] = item;
        };

        this.ReservationCount = function(userID)
        {
            var count = 0;
            for (var roomItemIndex in this.RoomItems)
            {
                if (this.RoomItems[roomItemIndex].ClaimedBy == userID)
                    count++;
            }
            return count;
        };
        
        this.IsSitting = function(userID)
        {
            for (var roomItemIndex in this.RoomItems)
            {
                if (this.RoomItems[roomItemIndex].ClaimedBy == userID)
                    return true;
            }
            return false;
        };

        this.Draw = function()
        {

            this.Canvas.css("width", this.Width);
            this.Canvas.css("height", this.Height);


            //
            //	cycle through items
            //

            for (var roomItemIndex in this.RoomItems)
            {
                this.RoomItems[roomItemIndex].Draw(this.Canvas, this.Zoom, this.Admin);
            }

            if (this.Admin)
            {
                $j(".scRoomItemContainer").draggable({
                    stop: function(event, ui)
                    {
                        room.RoomItems[$j(this).attr("id")].Left = ui.position.left;
                        room.RoomItems[$j(this).attr("id")].Top = ui.position.top;
                    }
                });
            }

            this.Canvas.show();

        };

        this.Save = function()
        {
            $j("#wpsc_json").val(JSON.stringify(this, null));
        };
    }

    function LoadPublicTable()
    {

        room = new Room(roomWidth, roomHeight, roomZoom, $j("#divRoomLive"), false);

        for (var y = 0; y < items.length; y++)
        {
            var item = new RoomItem(parseInt(items[y]["wpsc_ItemTypeID"]), parseInt(items[y]["wpsc_Claimable"]), parseInt(items[y]["wpsc_ClaimedBy"]), parseInt(items[y]["wpsc_X"]), parseInt(items[y]["wpsc_Y"]), items[y]["wpsc_Direction"], items[y]["wpsc_ZIndex"], items[y]["wpsc_SeatingChartID"]);

            room.AddItem(item);
        }

        room.Draw();

    }

<?php if (false): ?>
    </script>
<?php endif; ?>
