<?php
/**
 * Created by PhpStorm.
 * User: mingming
 * Date: 2018/6/5
 * Time: 下午3:22
 */

namespace App\Http\Controllers;



use Illuminate\Http\Request;

class FaceController extends Controller
{
    const FACE_IS_ALREADY_EXIST_CODE = 223105;
    //人脸识别接口对象
    private $aipClient = null;

    //图片类型
    private $imageType = "URL";

    //人脸库组别名称
    private $groupId = "sun";

    public function __construct()
    {
        $this->aipClient = new \AipFace(env('BAIDU_API_ID'), env('BAIDU_API_KEY'), env('BAIDU_SECRET_KEY'));
    }

    public function getFaceImage(Request $request) {
        var_dump($request);
    }

    //人脸注册
    public function create(Request $request) {
        var_dump($request->input("photo"),$_FILES, $_REQUEST);
        exit;
        $image = $request->input("image");

        $image = "http://imgstore.cdn.sogou.com/app/a/100540002/712864.jpg";

        $userId = $request->input("userId");

        // 如果有可选参数
        $options = array();
        $options["user_info"] = $image;
        $options["quality_control"] = "NORMAL";
        $options["liveness_control"] = "LOW";
        $result = $this->aipClient->addUser($image, $this->imageType, $this->groupId, $userId, $options);

        //若已经存在则更新
        if ($result['error_code'] == self::FACE_IS_ALREADY_EXIST_CODE) {
            $result = $this->aipClient->updateUser($image, $this->imageType, $this->groupId, $userId, $options);
        }

        echo json_encode($result);
    }

    //人脸搜索
    public function search(Request $request) {

        $image = "http://imgstore.cdn.sogou.com/app/a/100540002/712864.jpg";

        $groupIdList = "sym," . $this->groupId;

        // 调用人脸搜索
        $result = $this->aipClient->search($image, $this->imageType, $groupIdList);


        $userList = [];
        if (!$result['error_code']) {
            $userList = $result['result']['user_list'] ?? [];
        }

        echo $this->responseJson($result, ['user_list' => $userList]);
    }

    private function responseJson($_result, $data = []) {
        $result = ['error_code' => $_result['error_code'], 'error_msg' => $_result['error_msg']];

        $result = array_merge($result, $data);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}