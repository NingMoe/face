<?php
/**
 * Created by PhpStorm.
 * User: mingming
 * Date: 2018/6/5
 * Time: 下午3:22
 */

namespace App\Http\Controllers;



use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        echo md5("明月清风[机智]https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTJ9W9hqWcg1nzt9L7m4Gsp83mCs4VF7bHRdaHoPyHjrZMfxX3o0cNPia9kgCxEBzkKiaRpsQiakVVtxg/132");
    }

    //
    public function savePhoto(Request $request) {
        $file = $request->file("photo");
        // 文件是否上传成功
        $data = [];
        $result = ['error_code' => 0, "error_msg" => "success"];
        if ($file->isValid()) {
            // 获取文件相关信息
            $originalName = $file->getClientOriginalName(); // 文件原名
            $ext = $file->getClientOriginalExtension();     // 扩展名
            $realPath = $file->getRealPath();   //临时文件的绝对路径
            $type = $file->getClientMimeType();     // image/jpeg

            // 上传文件
            $filename = date('Y-m-d-H-i-s') . '-' . uniqid() . '.' . $ext;
            // 使用我们新建的uploads本地存储空间（目录）
            $ok = Storage::disk('uploads')->put($filename, file_get_contents($realPath));
            if (!$ok) {
                echo $this->responseJson(['error_code' => 100, "error_msg" => "file save failed"]);
                exit;
            }
            $data['photo'] = env('APP_URL') . "/uploads/" . $filename;
        }

        echo $this->responseJson($result, $data);
    }

    //人脸注册
    public function create(Request $request) {
        $image = $request->input("photo");
        $userinfo = $request->input("userinfo");
        if (empty($image) || empty($userinfo)) {
            $result = ['error_code' => 100, "error_msg" => "用户信息或图片为空"];
            exit($this->responseJson($result));
        }
        $userId = md5($userinfo);
        // 如果有可选参数
        $options = [];
        $options["user_info"] = $image;
        $result = $this->aipClient->addUser($image, $this->imageType, $this->groupId, $userId, $options);

        //若已经存在则更新
        if ($result['error_code'] == self::FACE_IS_ALREADY_EXIST_CODE) {
            $result = $this->aipClient->updateUser($image, $this->imageType, $this->groupId, $userId, $options);
        }

        echo $this->responseJson($result);
    }

    //人脸搜索
    public function search(Request $request) {
        $image = $request->input("photo");
        $groupIdList = "sym," . $this->groupId;
        $options = [];
        $options["max_user_num"] = 4;

        // 调用人脸搜索
        $result = $this->aipClient->search($image, $this->imageType, $groupIdList, $options);

        $userList = [];
        if (!$result['error_code']) {
            $userList = $result['result']['user_list'] ?? [];
            foreach ($userList as $key => $user) {
                //排除自己
                if ($user['score'] == 100) {
                    unset($userList[$key]);
                    continue;
                }
                $userList[$key]['score'] = number_format($user['score'], 2);
            }
            //取前三张
            $userList = array_slice($userList, 0 ,3);
        }

        echo $this->responseJson($result, ['user_list' => $userList]);
    }

    private function responseJson($_result, $data = []) {
        $result = ['error_code' => $_result['error_code'], 'error_msg' => $_result['error_msg']];

        if (!empty($data) && is_array($data)) {
            $result = array_merge($result, $data);
        }
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}