<?php
/**
 * @filesource modules/booking/models/categories.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Booking\Categories;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * หมวดหมู่.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{

  /**
   * อ่านหมวดหมู่สำหรับใส่ลงใน DataTable
   * ถ้าไม่มีคืนค่าข้อมูลเปล่าๆ 1 แถว.
   *
   * @param string $type
   *
   * @return array
   */
  public static function toDataTable($type)
  {
    // ภาษาที่ติดตั้ง
    $anguages = Language::installedLanguage();
    // Query ข้อมูลหมวดหมู่จากตาราง category
    $query = static::createQuery()
      ->select('id', 'category_id', 'topic')
      ->from('category')
      ->where(array('type', $type))
      ->order('category_id');
    $result = array();
    foreach ($query->execute() as $item) {
      $result[$item->id] = array(
        'id' => $item->id,
        'category_id' => $item->category_id,
      );
      $topic = json_decode($item->topic, true);
      foreach ($anguages as $lng) {
        $result[$item->id][$lng] = is_array($topic) && isset($topic[$lng]) ? $topic[$lng] : '';
      }
    }
    if (empty($result)) {
      $result[0] = array(
        'id' => 0,
        'category_id' => 1,
      );
      foreach ($anguages as $lng) {
        $result[0][$lng] = '';
      }
    }

    return $result;
  }

  /**
   * บันทึกหมวดหมู่.
   *
   * @param Request $request
   */
  public function submit(Request $request)
  {
    $ret = array();
    // session, token, can_config
    if ($request->initSession() && $request->isSafe() && $login = Login::isMember()) {
      if (Login::notDemoMode($login) && Login::checkPermission($login, 'can_config')) {
        // ค่าที่ส่งมา
        $type = $request->post('type')->topic();
        $save = array();
        $category_exists = array();
        foreach ($request->post('category_id')->toInt() as $key => $value) {
          if (isset($category_exists[$value])) {
            $ret['ret_category_id_'.$key] = Language::replace('This :name already exist', array(':name' => 'ID'));
          } else {
            $category_exists[$value] = $value;
            $save[$key]['category_id'] = $value;
          }
        }
        foreach (Language::installedLanguage() as $lng) {
          foreach ($request->post($lng)->topic() as $key => $value) {
            if ($value != '') {
              $save[$key]['topic'][$lng] = $value;
            }
          }
        }
        if (empty($ret)) {
          // ชื่อตาราง
          $table_name = $this->getTableName('category');
          // db
          $db = $this->db();
          // ลบข้อมูลเดิม
          $db->delete($table_name, array('type', $type), 0);
          // เพิ่มข้อมูลใหม่
          foreach ($save as $item) {
            if (isset($item['topic'])) {
              $item['topic'] = json_encode($item['topic']);
              $item['type'] = $type;
              $db->insert($table_name, $item);
            }
          }
          // คืนค่า
          $ret['alert'] = Language::get('Saved successfully');
          $ret['location'] = 'reload';
          // เคลียร์
          $request->removeToken();
        }
      }
      // คืนค่า JSON
      echo json_encode($ret);
    }
  }
}