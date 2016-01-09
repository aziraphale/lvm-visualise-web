<?php

// @todo Also try to find the filesystem usage for each LV

require __DIR__ . '/vendor/autoload.php';

/*
PV"       ,"DevSize","PV UUID",                               "VG",  "VG UUID",                               "Attr",  "VSize", "VFree","Ext",  "Fmt", "Attr","PSize", "PFree",  "PE",
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721",
        "Start","SSize","LV","LV UUID","Parent","Start","SSize","PE Ranges","Devices","Type","Attr","Layout","Role","Origin","Snap%","Cpy%Sync","Move","Log","#Str
        "0","30575","","","","0","30575","","","free","","unknown","public","","","","","","0
    "30575","7936","[sys-root_mimage_0]","JSYd0p-FZZP-JoMt-gbpK-CEfb-AasK-kApdmU","sys-root","0","7936","/dev/sda1:30575-38510","/dev/sda1(30575)","linear","iwi-aom---","linear","private,mirror,image","","","","","","1
    "38511","1","[pictures_mlog]","UiBG7u-QuCb-BwdS-yEDf-wSYA-C2du-BzuRlk","pictures","0","1","/dev/sda1:38511-38511","/dev/sda1(38511)","linear","lwi-aom---","linear","private,mirror,log","","","","","","1
    "38512","1","[tv_mlog]","KfrGBc-C2nr-ZZeE-KmTP-U0jO-R3jQ-zOlnlX","tv","0","1","/dev/sda1:38512-38512","/dev/sda1(38512)","linear","lwi-aom---","linear","private,mirror,log","","","","","","1
    "46193","10528","","","","0","10528","","","free","","unknown","public","","","","","","0
/dev/sdb1","3.64t","hBhxWU-jwHk-cgTf-OBgD-TeNO-euHa-Y2BeKw","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","3.64t","0 ","953861","0","953861","[tv_mimage_1]","PBYYu7-kGxa-2cYh-KMYG-j072-vRfQ-eX0HAY","tv","953861","953861","/dev/sdb1:0-953860","/dev/sdb1(0)","linear","iwi-aom---","linear","private,mirror,image","","","","","","1
*/
/*
PV","DevSize","PV UUID","VG","VG UUID","Attr","VSize","VFree","Ext","Fmt","Attr","PSize","PFree","PE","Start","SSize","LV","LV UUID","Parent","Start","SSize","PE Ranges","Devices","Type","Attr","Layout","Role","Origin","Snap%","Cpy%Sync","Move","Log","#Str
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","0","30575","","","","0","30575","","","free","","unknown","public","","","","","","0
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","30575","7936","[sys-root_mimage_0]","JSYd0p-FZZP-JoMt-gbpK-CEfb-AasK-kApdmU","sys-root","0","7936","/dev/sda1:30575-38510","/dev/sda1(30575)","linear","iwi-aom---","linear","private,mirror,image","","","","","","1
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","38511","1","[pictures_mlog]","UiBG7u-QuCb-BwdS-yEDf-wSYA-C2du-BzuRlk","pictures","0","1","/dev/sda1:38511-38511","/dev/sda1(38511)","linear","lwi-aom---","linear","private,mirror,log","","","","","","1
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","38512","1","[tv_mlog]","KfrGBc-C2nr-ZZeE-KmTP-U0jO-R3jQ-zOlnlX","tv","0","1","/dev/sda1:38512-38512","/dev/sda1(38512)","linear","lwi-aom---","linear","private,mirror,log","","","","","","1
/dev/sda1","221.57g","eh25UN-EQ2P-3TTA-JBed-Hrj0-qwcm-flP65w","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","221.57g","160.56g","56721","46193","10528","","","","0","10528","","","free","","unknown","public","","","","","","0
/dev/sdb1","3.64t","hBhxWU-jwHk-cgTf-OBgD-TeNO-euHa-Y2BeKw","pool","zApnUj-bLRF-wmXo-Jpic-ZuBy-s8fD-iLof7b","wz--n-","32.96t","7.19t","4.00m","lvm2","a--","3.64t","0 ","953861","0","953861","[tv_mimage_1]","PBYYu7-kGxa-2cYh-KMYG-j072-vRfQ-eX0HAY","tv","953861","953861","/dev/sdb1:0-953860","/dev/sdb1(0)","linear","iwi-aom---","linear","private,mirror,image","","","","","","1
*/
