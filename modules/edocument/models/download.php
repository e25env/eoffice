<?php
/**
 * @filesource modules/edocument/models/download.php
 *
 * @copyright 2016 Goragod.com
 * @license http://www.kotchasan.com/license/
 *
 * @see http://www.kotchasan.com/
 */

namespace Edocument\Download;

use Gcms\Login;
use Kotchasan\Http\Request;
use Kotchasan\Language;

/**
 * โมเดลสำหรับดาวน์โหลดเอกสาร.
 *
 * @author Goragod Wiriya <admin@goragod.com>
 *
 * @since 1.0
 */
class Model extends \Kotchasan\Model
{
    /**
     * @param Request $request
     */
    public function index(Request $request)
    {
        $ret = array();
        // session, referer, member, ไม่ใช่สมาชิกตัวอย่าง
        if ($request->initSession() && $request->isReferer() && $login = Login::isMember()) {
            if (Login::notDemoMode($login) && preg_match('/download_([0-9]+)/', $request->post('id')->toString(), $match)) {
                $model = new static();
                // อ่านรายการที่เลือก
                $result = $model->db()->createQuery()
                    ->from('edocument E')
                    ->join('edocument_download D', 'INNER', array(array('D.id', 'E.id'), array('D.member_id', (int) $login['id'])))
                    ->where(array('E.id', (int) $match[1]))
                    ->groupBy('E.id')
                    ->first('E.id', 'E.topic', 'D.id download_id', 'D.downloads', 'E.file', 'E.ext');
                if ($result) {
                    // ไฟล์
                    $file = ROOT_PATH.DATA_FOLDER.'edocument/'.$result->file;
                    if (is_file($file)) {
                        // อัปเดทดาวน์โหลด
                        $model->db()->update($model->getTableName('edocument_download'), array(
                            array('id', $result->id),
                            array('member_id', (int) $login['id']),
                        ), array(
                            'downloads' => (int) $result->downloads + 1,
                            'last_update' => time(),
                        ));
                        // id สำหรบไฟล์ดาวน์โหลด
                        $id = \Kotchasan\Text::rndname(32);
                        // บันทึกรายละเอียดการดาวน์โหลดลง SESSION
                        $_SESSION[$id] = array(
                            'file' => $file,
                            'name' => self::$cfg->edocument_download_action == 1 ? '' : $result->topic.'.'.$result->ext,
                            'mime' => self::$cfg->edocument_download_action == 1 ? \Kotchasan\Mime::get($result->ext) : 'application/octet-stream',
                        );
                        // คืนค่า
                        $ret['target'] = self::$cfg->edocument_download_action;
                        $ret['url'] = WEB_URL.'modules/edocument/filedownload.php?id='.$id;
                    } else {
                        // ไม่พบไฟล์
                        $ret['alert'] = Language::get('File not found');
                    }
                    $ret['modal'] = 'close';
                }
            }
        }
        if (empty($ret)) {
            $ret['alert'] = Language::get('Unable to complete the transaction');
        }
        // คืนค่าเป็น JSON
        echo json_encode($ret);
    }

    /**
     * อ่านเอกสารที่ $id
     * ไม่พบ คืนค่า null.
     *
     * @param int $id
     *
     * @return object
     */
    public static function get($id, $login)
    {
        $search = static::createQuery()
            ->from('edocument A')
            ->join('edocument_download E', 'INNER', array(array('E.id', 'A.id'), array('E.member_id', (int) $login['id'])))
            ->where(array('A.id', $id))
            ->first('A.id', 'A.document_no', 'E.downloads', 'A.topic', 'A.ext', 'A.sender_id', 'A.size', 'A.last_update', 'A.department', 'A.detail');
        if ($search) {
            $search->department = explode(',', trim($search->department, ','));
        }

        return $search;
    }
}
