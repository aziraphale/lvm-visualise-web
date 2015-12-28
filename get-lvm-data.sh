#!/bin/sh

# Physical volume data (with segments of everything - but doesn't include top-level LVs of mirrors)
pvs --separator '","' --segments -o pv_name,dev_size,pv_uuid,vg_name,vg_uuid,vg_attr,vg_size,vg_free,vg_extent_size,pv_fmt,pv_attr,pv_size,pv_free,pv_pe_count,pvseg_start,pvseg_size,lv_name,lv_uuid,lv_parent,seg_start_pe,seg_size_pe,seg_pe_ranges,devices,segtype,lv_attr,lv_layout,lv_role,origin,snap_percent,move_pv,stripes

# Logical volume data (including mirrored LVs, but no segment data)
lvs --separator '","' --segments -o vg_uuid,lv_name,lv_uuid,segtype,lv_attr,lv_layout,lv_role,copy_percent,move_pv,mirror_log,stripes
