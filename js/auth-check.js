/**
 * 로그인 체크 스크립트
 * 회원 전용 페이지에서 로그인 여부를 확인하고, 미로그인 시 로그인 페이지로 리다이렉트
 */

(function() {
    'use strict';

    // 로그인이 필요없는 페이지 목록
    const PUBLIC_PAGES = [
        'login.html',
        'signup.html',
        'signup_big.html',
        'index.html',
        'test-login.html',
        'referral-link-test.html'
    ];

    // 현재 페이지가 로그인이 필요없는 페이지인지 확인
    function isPublicPage() {
        const currentPage = window.location.pathname.split('/').pop();
        return PUBLIC_PAGES.includes(currentPage);
    }

    // 로그인 체크
    async function checkAuth() {
        // 로그인이 필요없는 페이지면 체크하지 않음
        if (isPublicPage()) {
            return;
        }

        // 1. 일반 회원 세션 확인
        try {
            const userResponse = await fetch('/api/auth/check-session.php', {
                credentials: 'include'
            });
            const userData = await userResponse.json();

            if (userData.success && userData.logged_in) {
                // 일반 회원 로그인 상태이면 리다이렉트하지 않음
                console.log('일반 회원 세션 확인됨:', userData.user_id);
                // session_token 복원
                if (userData.token) {
                    localStorage.setItem('session_token', userData.token);
                }
                return;
            }
        } catch (error) {
            console.log('일반 회원 세션 체크 실패:', error);
        }

        // 2. 관리자 세션 확인
        try {
            const response = await fetch('/admin/api/auth.php?action=check', {
                credentials: 'include'
            });
            const data = await response.json();

            if (data.logged_in) {
                // 관리자 로그인 상태이면 리다이렉트하지 않음
                console.log('관리자 세션 확인됨');
                return;
            }
        } catch (error) {
            console.log('관리자 세션 체크 실패:', error);
        }

        // 로그인되지 않은 상태 - 로그인 페이지로 리다이렉트
        console.log('로그인이 필요합니다. 로그인 페이지로 이동합니다.');

        // 현재 페이지 URL을 저장 (로그인 후 돌아올 수 있도록)
        const currentUrl = window.location.href;
        sessionStorage.setItem('redirect_after_login', currentUrl);

        // 로그인 페이지로 리다이렉트
        window.location.href = getLoginPagePath();
    }

    // 로그인 페이지 경로 계산
    function getLoginPagePath() {
        const currentPath = window.location.pathname;
        const pathSegments = currentPath.split('/');

        // html 디렉토리에 있는 경우
        if (pathSegments.includes('html')) {
            return 'login.html';
        }

        // 기본값
        return '/html/login.html';
    }

    // 페이지 로드 시 즉시 실행
    checkAuth();

    // 전역 함수로 노출 (다른 스크립트에서 사용 가능)
    window.checkAuth = checkAuth;
})();
