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
        var_dump($request);
    }

    //人脸注册
    public function create(Request $request) {
        $file = $request->file("photo");
        // 文件是否上传成功
        $result = ['error_code' => 100, "error_msg" => "un valid file"];
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

//            $image = "http://face.anlaosun.xyz/uploads/" . $filename;
            $image = env('APP_URL') . "/uploads/" . $filename;
            $image = "http://face.anlaosun.xyz/uploads/2018-06-07-01-25-02-5b18896e40089.jpg";
            echo $image;
            $userId = time() . mt_rand(1000, 9999);
            // 如果有可选参数
            $options = array();
            $options["user_info"] = $image;
            $options["quality_control"] = "NORMAL";
            $options["liveness_control"] = "NONE";
            $result = $this->aipClient->addUser($image, $this->imageType, $this->groupId, $userId, $options);

            //若已经存在则更新
            if ($result['error_code'] == self::FACE_IS_ALREADY_EXIST_CODE) {
                $result = $this->aipClient->updateUser($image, $this->imageType, $this->groupId, $userId, $options);
            }
        }


        echo $this->responseJson($result);
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
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }
}