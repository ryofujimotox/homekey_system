<?php

declare(strict_types=1);

namespace App\Controller\Api;

//
use App\Controller\AppController as BaseController;
use Cake\Event\Event;
use App\Utils\CustomUtility;
use Cake\Routing\Router;
use Cake\Auth\DefaultPasswordHasher;
use \SplFileObject;
use Cake\Utility\Hash;
use Cake\Http\Response;
//
use Cake\Http\Exception\NotFoundException;

if (false) {
    /**
 * @OA\Server(
 *   description="ローカル",
 *   url="http://localhost:8080"
 * )
 */
}
/**
 * @OA\Info(
 *   title="ポートAPI",
 *   description="用のAPIです。",
 *   version="1.0.0",
 * )
 */
class AppController extends BaseController {
    public function initialize(): void {
        parent::initialize();

        $this->cacheOn();
    }

    /**
     *
     * クエリを検証してから取得する。
     *
     */
    public function getGetQuery($list) {
        $_list = array_column($list, null, 'key');

        // バリデーション
        $result = [];
        foreach ($this->request->getQuery() as $key => $value) {
            $option = $_list[$key] ?? [];
            $type = $option['type'] ?? '';
            if (!$type) {
                throw new NotFoundException(__('不正なキーです'));
            }

            if ($type == 'string') {
                $_max_length = $option['maxLength'] ?? 500;
                if (mb_strlen($value) > $_max_length) {
                    throw new NotFoundException(__('長すぎます'));
                }
            }

            //
            if ($type == 'int_array') {
                if (!$value) {
                    continue;
                }
                $value_array = explode(',', $value);
                foreach ($value_array as $_val) {
                    if (!is_numeric($_val)) {
                        throw new NotFoundException(__('数値である必要があります'));
                    }
                }

                $value = $value_array;
            }

            if ($type == 'int') {
                if (!is_numeric($value)) {
                    throw new NotFoundException(__('数値である必要があります'));
                }
                $_max_value = $option['maxValue'] ?? 0;
                if ($_max_value) {
                    if ($value > $_max_value) {
                        throw new NotFoundException(__('不正な値が含まれています'));
                    }
                }

                $_allowValue = $option['allowValue'] ?? [];
                if ($_allowValue) {
                    if (!in_array($value, $_allowValue)) {
                        throw new NotFoundException(__('不正な値が含まれています'));
                    }
                }
            }

            //
            $result[$key] = $value;
        }

        // デフォルト値設定
        $default = array_combine(array_column($list, 'key'), array_column($list, 'default'));
        return array_merge($default, $result);
    }

    /**
     *
     * API返却 getErros
     * entityのエラーを返す
     *
     */
    public function setApiErrors($errors) {
        $datas = array_map(function ($_column, $_errors) {
            return [
                'column' => $_column,
                'message' => array_values($_errors)[0] ?? ''
            ];
        }, array_keys($errors), $errors);
        $code = $datas ? RESPONCE_CODE_ERROR : RESPONCE_CODE_SUCCESS;
        return $this->setApi(['errors' => $datas], $code);
    }

    /**
     *
     * API返却
     * 成功か失敗のメッセージを返すだけ。
     *
     */
    public function setApiBoolean(Bool $is_success, string $message_success = '', string $message_failed = '') {
        $code = $is_success ? RESPONCE_CODE_SUCCESS : RESPONCE_CODE_ERROR;
        $message = $is_success ? $message_success : $message_failed;
        return $this->setApi(['message' => $message], $code);
    }

    /**
     *
     * API返却
     * データを返すだけ
     *
     */
    public function setApiData($data, string $message_success = '', string $message_failed = '') {
        $code = $data ? RESPONCE_CODE_SUCCESS : RESPONCE_CODE_ERROR;
        $message = $data ? $message_success : $message_failed;
        return $this->setApi(['data' => $data, 'message' => $message], $code);
    }

    /**
     *
     * API返却
     *
     */
    public function setApi($datas, $code = RESPONCE_CODE_SUCCESS) {
        $result = array_merge(
            [
                'code' => $code
            ],
            $datas
        );

        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (false !== strpos($host, 'localhost')) {
            $this->cors();
        }

        ob_start('ob_gzhandler');

        $this->set($result);
        $this->viewBuilder()->setOption('serialize', array_keys($result));
    }

    public function cacheOn() {
        $this->cacheOff();
        header('Cache-Control: max-age=10, stale-while-revalidate=864000000, stale-if-error=864000');
    }

    public function cacheOnStrong($day = 1) {
        $this->cacheOff();
        header('Cache-Control: max-age=' . (86400 * $day) . ', stale-while-revalidate=864000000, stale-if-error=864000');
    }

    public function cacheOff() {
        header_remove('Expires');
        header_remove('Pragma');
        header_remove('Cache-Control');
    }

    /**
     *
     * 使わない？
     *
     */
    public function cors() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: *');

        $this->response = $this->response->cors($this->request)
        ->allowOrigin(['*'])
        ->allowMethods(['*'])
        ->allowHeaders(['x-xsrf-token', 'Origin', 'Content-Type', 'X-Auth-Token'])
        ->allowCredentials(['true'])
        ->exposeHeaders(['Link'])
        ->maxAge(300)
        ->build();
    }

     /**
     * @OA\Post(
     *   path="/api/example/{id}",
     *   summary="具体例が無かったので寄せ集めてみた",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"number", "text"},
     *       @OA\Property(
     *         property="number",
     *         type="integer",
     *         format="int32",
     *         example=1,
     *         description="リクエストボディのjsonのプロパティの例"
     *       ),
     *       @OA\Property(
     *         property="text",
     *         type="string",
     *         example="text",
     *         description="リクエストボディのjsonのプロパティの例"
     *       )
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="パスに含めるパラメータ",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="queryString",
     *     in="query",
     *     required=true,
     *     description="GETクエリーのパラメータ",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         description="レスポンスボディjsonパラメータの例"
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Bad Request",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(
     *         property="message",
     *         type="string",
     *         description="レスポンスボディjsonパラメータの例"
     *       )
     *     )
     *   )
     * )
     */
    // function test(){
    //     $this->setApiDatas(["TEST" => "TEST"]);
    // }
}
