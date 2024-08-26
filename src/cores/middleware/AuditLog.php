<?php

namespace cores\middleware;

use cores\Request;
use think\facade\Log;
use think\Response;

class AuditLog
{

    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);
        $audit = $this->getAuditLogInfo($request, $response);
        if ($audit) {
            Log::record($audit, 'audit');
        }
        return $response;
    }

    private function getAuditLogInfo(Request $request, Response $response): array
    {
        // 判断是否记录审核日志
        $res = $response->getData();
        if ($response->getCode() != 200) {
            return [];
        }

        $status = $res['code'] ?? 0;
        $ip = $request->ip();
        $path = $request->baseUrl();
        $action = $request->action();
        $params = $request->param();
        $refer = $request->header('referer');
        $saas_id = \getSaasId();
        $user_id = 0;
        $user_name = '';
        if ($user = $request->user()) {
            $user_id = $user->id;
            $user_name = $user->real_name ?? $user->nick_name ?? '';
        }

        // 敏感字段过滤
        $params = $this->filterAndMasking($params);
        $response = $res;

        return compact("ip", "path", "action", "params", "saas_id", 'user_id', 'user_name', 'response', 'refer', 'status');
    }

    private function filterAndMasking($params)
    {
        $maskingFields = config('app.masking_fields') ?? [];
        if (empty($maskingFields)) {
            return $params;
        }

        foreach ($params as $field => &$value) {
            if (is_array($value)) {
                $value = $this->filterAndMasking($value);
                continue;
            }

            if (is_string($field) && in_array($field, $maskingFields)) {
                $value = "*******";
            }
        }
        return $params;
    }
}