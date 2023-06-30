<?php


namespace App\Helpers;


use App\Helpers\ResponseEnum;
use App\Exceptions\BusinessException;
use Illuminate\Validation\Rule;

trait VerifyRequestInput
{
    use ApiResponse;

    /**
     * 验证ID
     * @param $key
     * @param null $default
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyId($key, $default=null)
    {
        return $this->verifyData($key, $default, 'integer|digits_between:1,20');
    }

    /**
     * 验证是否为整数
     * @param $key
     * @param null $default
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyInteger($key, $default=null)
    {
        return $this->verifyData($key, $default, 'integer');
    }

    /**
     * 验证是否为数字
     * @param $key
     * @param null $default
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyNumeric($key, $default=null)
    {
        return $this->verifyData($key, $default, 'numeric');
    }

    /**
     * 验证是否为字符串
     * @param $key
     * @param null $default
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyString($key, $default=null)
    {
        return $this->verifyData($key, $default, 'string');
    }

    /**
     * 验证是否为布尔值
     * @param $key
     * @param null $default
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyBoolean($key, $default=null)
    {
        return $this->verifyData($key, $default, 'boolean');
    }

    /**
     * 验证是否为枚举
     * @param $key
     * @param null $default
     * @param array $enum
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyEnum($key, $default=null, array $enum=[])
    {
        return $this->verifyData($key, $default, Rule::in($enum));
    }

    /**
     * 自定义校验参数
     * @param $key string 字段
     * @param $default string 默认值
     * @param $rule string 验证规则
     * @return mixed|null
     * @throws BusinessException
     */
    public function verifyData($key, $default, $rule)
    {
        $value = request()->input($key, $default);
        $validator = \Validator::make([$key => $value], [$key => $rule]);
        if (is_null($value)){
            $this->throwBusinessException(ResponseEnum::CLIENT_PARAMETER_ERROR);
        }
        if ($validator->fails()){
            $this->throwBusinessException(ResponseEnum::CLIENT_PARAMETER_ERROR, $validator->errors()->first());
        }
        return $value;
    }

}
