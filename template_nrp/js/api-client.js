// ===================================================================
// Neural Pulse Network - API Client
// ===================================================================
// 백엔드 API 호출을 위한 클라이언트 라이브러리
// ===================================================================

const API_BASE_URL = window.location.origin; // 동일 도메인 (Cafe24)
// 개발 환경에서는: const API_BASE_URL = 'http://localhost';

const APIClient = {
    /**
     * API 호출 (공통 함수)
     * @param {string} endpoint API 엔드포인트
     * @param {object} options Fetch 옵션
     * @returns {Promise} API 응답
     */
    async request(endpoint, options = {}) {
        const url = `${API_BASE_URL}${endpoint}`;

        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            ...options
        };

        try {
            const response = await fetch(url, defaultOptions);
            const data = await response.json();

            return {
                success: response.ok,
                status: response.status,
                data: data
            };
        } catch (error) {
            console.error('API Request Error:', error);
            return {
                success: false,
                status: 0,
                data: {
                    success: false,
                    message: '네트워크 오류가 발생했습니다.'
                }
            };
        }
    },

    /**
     * 회원가입
     * @param {object} userData 사용자 데이터
     * @returns {Promise} API 응답
     */
    async register(userData) {
        return await this.request('/api/auth/register.php', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    },

    /**
     * 로그인
     * @param {string} userId 사용자 ID
     * @param {string} password 비밀번호
     * @returns {Promise} API 응답
     */
    async login(userId, password) {
        return await this.request('/api/auth/login.php', {
            method: 'POST',
            body: JSON.stringify({ user_id: userId, password })
        });
    },

    /**
     * 로그아웃
     * @param {string} token 세션 토큰
     * @returns {Promise} API 응답
     */
    async logout(token) {
        return await this.request('/api/auth/logout.php', {
            method: 'POST',
            body: JSON.stringify({ token })
        });
    },

    /**
     * ID 중복 체크
     * @param {string} userId 사용자 ID
     * @returns {Promise} API 응답
     */
    async checkUserId(userId) {
        return await this.request('/api/auth/check-id.php', {
            method: 'POST',
            body: JSON.stringify({ user_id: userId })
        });
    },

    /**
     * 이메일 인증 코드 발송
     * @param {string} email 이메일 주소
     * @returns {Promise} API 응답
     */
    async sendVerificationCode(email) {
        return await this.request('/api/email/send-code.php', {
            method: 'POST',
            body: JSON.stringify({ email })
        });
    },

    /**
     * 이메일 인증 코드 확인
     * @param {string} email 이메일 주소
     * @param {string} code 인증 코드
     * @returns {Promise} API 응답
     */
    async verifyCode(email, code) {
        return await this.request('/api/email/verify-code.php', {
            method: 'POST',
            body: JSON.stringify({ email, code })
        });
    },

    /**
     * USDT 주소 중복 체크
     * @param {string} usdtAddress USDT 주소
     * @returns {Promise} API 응답
     */
    async checkUsdtAddress(usdtAddress) {
        return await this.request('/api/auth/check-usdt.php', {
            method: 'POST',
            body: JSON.stringify({ usdt_address: usdtAddress })
        });
    }
};

// 세션 관리 헬퍼
const SessionManager = {
    /**
     * 세션 토큰 저장
     * @param {string} token 세션 토큰
     * @param {object} user 사용자 정보
     */
    setSession(token, user) {
        localStorage.setItem('neural_token', token);
        localStorage.setItem('neural_user', JSON.stringify(user));
    },

    /**
     * 세션 토큰 가져오기
     * @returns {string|null} 세션 토큰
     */
    getToken() {
        return localStorage.getItem('neural_token');
    },

    /**
     * 사용자 정보 가져오기
     * @returns {object|null} 사용자 정보
     */
    getUser() {
        const user = localStorage.getItem('neural_user');
        return user ? JSON.parse(user) : null;
    },

    /**
     * 로그인 여부 확인
     * @returns {boolean} 로그인 여부
     */
    isLoggedIn() {
        return this.getToken() !== null;
    },

    /**
     * 세션 삭제
     */
    clearSession() {
        localStorage.removeItem('neural_token');
        localStorage.removeItem('neural_user');
    },

    /**
     * 로그아웃
     */
    async logout() {
        const token = this.getToken();
        if (token) {
            await APIClient.logout(token);
        }
        this.clearSession();
        window.location.href = 'index.html';
    }
};

// 페이지 로드 시 로그인 상태 확인
document.addEventListener('DOMContentLoaded', () => {
    // 현재 페이지 파일명 추출
    const currentPage = window.location.pathname.split('/').pop() || 'index.html';

    // 로그인 없이 접근 가능한 페이지들 (index.html, signup.html, login.html)
    const publicPages = ['index.html', 'login.html', 'signup.html', ''];

    // public 페이지가 아니고 로그인하지 않은 경우
    if (!publicPages.includes(currentPage) && !SessionManager.isLoggedIn()) {
        // 로그인 페이지로 리다이렉트
        console.log('[Auth] Not logged in. Redirecting to login page...');
        window.location.href = 'login.html';
    }
});
