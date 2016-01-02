<?php
namespace Aziraphale\LVM;
use Aziraphale\LVM\Utility\LVAttributes;

/**
 * A public-facing LV - probably a standard, linear LV, but this class is
 *  extended to create other LV types, so that can't be guaranteed unless
 *  this object is exactly this class, rather than a subclass
 *
 * @package Aziraphale\LVM
 */
class PublicLV extends LV
{
    /**
     * lv_attr / Attr
     *
     * @var LVAttributes
     */
    public $attributes;

    /**
     * lv_layout / Layout
     *
     * @var string
     */
    public $layout;

    /**
     * lv_role / Role
     *
     * @var string
     */
    public $role;

    /**
     * move_pv /  Move
     *
     * @var string
     */
    public $movePhysicalVolume;

    /**
     * stripes / #Str
     *
     * @var int
     */
    public $stripesCount;

    /**
     * PublicLV constructor.
     *
     * @param LVM $lvm
     * @param VG $vg
     * @param string $uuid
     * @param string $name
     * @param LVAttributes $attributes
     * @param string $layout
     * @param string $role
     * @param string $movePhysicalVolume
     * @param int $stripesCount
     */
    public function __construct(LVM $lvm, VG $vg, $uuid, $name, LVAttributes $attributes, $layout, $role, $movePhysicalVolume, $stripesCount)
    {
        $this->attributes = $attributes;
        $this->layout = $layout;
        $this->role = $role;
        $this->movePhysicalVolume = $movePhysicalVolume;
        $this->stripesCount = $stripesCount;

        parent::__construct($lvm, $vg, $uuid, $name);
    }
}
