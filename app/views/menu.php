<?php
$menu = [
    "main-list" => [
        "0" => [
            "id" => "1",
            "name" => "Női",
            "link" => "?gender=female"
        ],


        "1" => [
            "id" => "2",
            "name" => "Férfi",
            "link" => "?gender=male"
        ],

        "2" => [
            "id" => "3",
            "name" => "Gyermek",
            "link" => "?gender=kids"
        ]
    ],
    "icon-list"=>[
        "0" => [
            "id" => "4",
            "name" => "Percentage",
            "link" => ""
        ],
        "1" => [
            "id" => "5",
            "name" => "Profile",
            "link" => ""
        ],
        "2" => [
            "id" => "6",
            "name" => "Bell",
            "link" => ""
        ],
        "3" => [
            "id" => "7",
            "name" => "Heart",
            "link" => ""
        ],
        "4" => [
            "id" => "8",
            "name" => "Bag",
            "link" => ""
        ],
    ],

    "sub-list" => [
        "0" => [
            "name" => "Kollekciók",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "1" => [
            "name" => "Ruházat",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "2" => [
            "name" => "Cipők",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "3" => [
            "name" => "Kiegészítők",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "4" => [
            "name" => "Streetwear",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "5" => [
            "name" => "Mi ajánljuk",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "6" => [
            "name" => "Top 100",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "7" => [
            "name" => "Márkák",
            "link" => "",
            "parent_id" => ["1", "2", "3"]
        ],
        "8" => [
            "name" => "Ispirációk",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "9" => [
            "name" => "Babák",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "10" => [
            "name" => "Lányok",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],
        "11" => [
            "name" => "Fiúk",
            "link" => "",
            "parent_id" => ["1", "2"]
        ],

    ]


];
?>

<menu class="flex w-full justify-between ">
    <div class="p-2 main-menu w-full flex justify-strech">
        <div class="categories-menu  flex items-center items-center">
            <?php
            foreach ($menu["main-list"] as $element ){ ?>
            <div id="<?= $element["id"]; ?>" class="py-3  px-1 cursor-pointer hover:border-b-2 border-solid"><?= $element["name"]; ?></div>
        <?php } ?>
        </div>
        <!--
    m: top ,right, bottom
    mx: left,right
    my: top, bottom
    ml:left
    mr:right
    mt:top
    mb:bottom
    Ugyan ez a padding csak p-vel
     -->
        <div class="logo mx-2 flex items-center">
            <span class="bg-black p-1 text-white mr-1">Fantasy</span>
            <span class="bg-black p-1 text-white"> Shop</span>
        </div>
        <div class="icons flex items-center">
            <i class="las la-bell"></i>
            <i class="las la-percent"></i>
            <i class="lar la-heart"></i>
            <i class="las la-shopping-bag"></i>
            <i class="las la-user"></i>
        </div>
    </div>
    <div class="flex">
        <div class="sub-categories">
            <div class="items"></div>
            <div></div>
            <div></div>
        </div>
        <div class="search-bar">
            <input type="text" id="search-bar" placeholder="Keress rá márkákra és még sok másra..." />
        </div>
    </div>
</menu>
