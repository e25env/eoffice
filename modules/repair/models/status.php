<?php
/**
 * @filesource modules/repair/modules/status.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Repair\Status;

/**
 * อ่านค่าสถานะของการซ่อม
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\KBase
{
    /**
     * @var mixed
     */
    private $statuses;
    /**
     * @var mixed
     */
    private $colors;

    /**
     * Query รายการสถานะทั้งหมดที่สามารถเผยแพร่ได้.
     *
     * @return array
     */
    public static function all()
    {
        return \Kotchasan\Model::createQuery()
            ->select('id', 'topic', 'color')
            ->from('category')
            ->where(array(
                array('type', 'repairstatus'),
                array('published', 1),
            ))
            ->order('id')
            ->toArray()
            ->execute();
    }

    /**
     * อ่านค่าสถานะ.
     *
     * @return array
     */
    public static function create()
    {
        $obj = new static();
        $obj->statuses = array();
        $obj->colors = array();
        foreach (self::all() as $item) {
            $obj->statuses[$item['id']] = $item['topic'];
            $obj->colors[$item['id']] = $item['color'];
        }

        return $obj;
    }

    /**
     * อ่านค่าสีที่ $id.
     *
     * @param int $id
     *
     * @return string
     */
    public function getColor($id)
    {
        return isset($this->colors[$id]) ? $this->colors[$id] : 'inherit';
    }

    /**
     * อ่านสถานะที่ $id.
     *
     * @param int $id
     *
     * @return string
     */
    public function get($id)
    {
        return isset($this->statuses[$id]) ? $this->statuses[$id] : 'Unknow';
    }

    /**
     * คืนค่าสถานะการซ่อมสำหรับใส่ลงใน select.
     *
     * @return array
     */
    public function toSelect()
    {
        return $this->statuses;
    }
}
