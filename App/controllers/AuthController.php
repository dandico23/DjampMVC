<?php

namespace Controller;

class AuthController extends \Engine\Controller
{
    //Confere se existe sessão
    //Precisa ser modificada para checar varieveis de sessão, já que é iniciada sessões em outras paginas;
    //Usar sessão para o sistema talves só seja possivel após a total migração, pois não é possivel deslogar apenas do nosso módulo.
    public function checkSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        return true;
    }

    //Passa os cookies para varieveis de sessão
    public function parseCookie($all = false, array $cookies = [])
    {
        if ($all) {
            foreach ($_COOKIE as $key => $val) {
                $_SESSION[$key] = $val;
            }
        } else {
            foreach ($cookies as $key => $val) {
                if (isset($_COOKIE[$val])) {
                    $_SESSION[$val] = $_COOKIE[$val];
                }
            }
        }
    }

    //Confere se os cookies existem;
    public function checkCookie(array $cookies)
    {
        foreach ($cookies as $key => $val) {
            if (!isset($_COOKIE[$val])) {
                return false;
            }
        }
        return true;
    }
    
    //ignorar até a versão final
    public function authMiddleware($request, $response, $next)
    {
        if (!$this->checkSession()) {
            //podemos puxar os cookies que serão checados de algum arquivo de configuração
            if (!$this->checkCookie([])) {
                return $this->view->render($response->withStatus(403), 'error/unauth.html');
            } else {
                if (!isset($_SESSION)) {
                    session_start();
                }
                $this->parseCookie($all = true);
            }
        }
        
        $response = $next($request, $response);
        return $response;
    }

    //autentica apenas com os cookies fornecidos em /config/cookies.ini
    public function authWithCookie($request, $response, $next)
    {
        if (!$this->checkCookie(parse_ini_file("../config/cookies.ini"))) {
            return $this->view->render($response->withStatus(403), 'error/unauth.html');
        }
        
        return $next($request, $response);
    }
}
