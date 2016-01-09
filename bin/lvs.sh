#!/bin/sh

# Logical volume data (including mirrored LVs, but no segment data)
lvs --separator '","' --segments -o vg_uuid,lv_name,lv_uuid,segtype,lv_attr,lv_layout,lv_role,copy_percent,move_pv,mirror_log,stripes
