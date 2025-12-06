// ===================================================================
// ADMIN POPUP MENU - Admin Navigation Component
// ===================================================================

const AdminPopupMenu = {
    // 팝업 메뉴 HTML 생성
    getMenuHTML: function() {
        return `
        <div class="admin-popup-menu" id="adminPopupMenu">
            <!-- Dashboard -->
            <a href="/admin/index.php" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>📊 대시보드</span>
                    </div>
                </div>
            </a>

            <!-- 회원 -->
            <a href="/admin/pages/users-manage.html" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>👥 회원</span>
                    </div>
                </div>
            </a>

            <!-- 아바타생성 -->
            <a href="/admin/pages/avatar-generator.php" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>🤖 아바타생성</span>
                    </div>
                </div>
            </a>

            <!-- 가입승인 -->
            <a href="/admin/pages/pending-registrations.php" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>📝 가입승인</span>
                    </div>
                </div>
            </a>

            <!-- 보너스 -->
            <a href="/admin/bonus-history.php" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>📊 보너스</span>
                    </div>
                </div>
            </a>

            <!-- 랭킹 -->
            <a href="/admin/bonus-rank.php" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>🏆 랭킹</span>
                    </div>
                </div>
            </a>

            <!-- 회원재무 -->
            <a href="/admin/member-financial.html" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>💰 회원재무</span>
                    </div>
                </div>
            </a>

            <!-- 조직도 -->
            <a href="/html/organization.html" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>🌳 조직도</span>
                    </div>
                </div>
            </a>

            <!-- 출금 -->
            <a href="/admin/pages/withdrawals.html" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>💸 출금</span>
                    </div>
                </div>
            </a>

            <!-- 공지사항 -->
            <a href="/admin/pages/notices.html" class="admin-menu-item admin-menu-link">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="admin-menu-icon"></div>
                        <span>📢 공지사항</span>
                    </div>
                </div>
            </a>

            <!-- 구분선 -->
            <div class="admin-menu-divider"></div>

            <!-- 로그아웃 -->
            <a href="#" class="admin-auth-button" id="adminLogoutButton">
                <div class="auth-button-inner">
                    <div class="admin-menu-icon"></div>
                    <span>🚪 로그아웃</span>
                </div>
            </a>
        </div>
        `;
    },

    // 팝업 메뉴 CSS 생성
    getMenuCSS: function() {
        return `
        /* Admin 팝업 메뉴 */
        .admin-popup-menu {
            position: fixed;
            top: 90px;
            right: 30px;
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.98), rgba(15, 23, 42, 0.98));
            backdrop-filter: blur(30px);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 16px;
            padding: 12px;
            z-index: 10000;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateY(-20px) scale(0.9);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            min-width: 220px;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .admin-popup-menu.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateY(0) scale(1);
        }

        /* 스크롤바 스타일 */
        .admin-popup-menu::-webkit-scrollbar {
            width: 6px;
        }

        .admin-popup-menu::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
            border-radius: 3px;
        }

        .admin-popup-menu::-webkit-scrollbar-thumb {
            background: rgba(59, 130, 246, 0.5);
            border-radius: 3px;
        }

        .admin-popup-menu::-webkit-scrollbar-thumb:hover {
            background: rgba(59, 130, 246, 0.7);
        }

        .admin-menu-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0;
            padding: 8px 14px;
            margin: 3px 0;
            background: rgba(59, 130, 246, 0.08);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            color: #e2e8f0;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .admin-menu-link {
            display: block;
            text-decoration: none;
        }

        .admin-menu-link .menu-title {
            pointer-events: none;
        }

        .admin-menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, rgba(59, 130, 246, 0.15), rgba(37, 99, 235, 0.15));
            transition: width 0.4s;
            z-index: -1;
            border-radius: 10px;
        }

        .admin-menu-item:hover::before {
            width: 100%;
        }

        .admin-menu-item:hover {
            border-color: #3b82f6;
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }

        .admin-menu-icon {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #3b82f6;
            box-shadow: 0 0 8px #3b82f6;
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        /* 메뉴 아이템 애니메이션 */
        .admin-menu-item:nth-child(1) { animation: slideIn 0.3s ease-out 0.05s backwards; }
        .admin-menu-item:nth-child(2) { animation: slideIn 0.3s ease-out 0.08s backwards; }
        .admin-menu-item:nth-child(3) { animation: slideIn 0.3s ease-out 0.11s backwards; }
        .admin-menu-item:nth-child(4) { animation: slideIn 0.3s ease-out 0.14s backwards; }
        .admin-menu-item:nth-child(5) { animation: slideIn 0.3s ease-out 0.17s backwards; }
        .admin-menu-item:nth-child(6) { animation: slideIn 0.3s ease-out 0.20s backwards; }
        .admin-menu-item:nth-child(7) { animation: slideIn 0.3s ease-out 0.23s backwards; }
        .admin-menu-item:nth-child(8) { animation: slideIn 0.3s ease-out 0.26s backwards; }
        .admin-menu-item:nth-child(9) { animation: slideIn 0.3s ease-out 0.29s backwards; }
        .admin-menu-item:nth-child(10) { animation: slideIn 0.3s ease-out 0.32s backwards; }
        .admin-menu-item:nth-child(11) { animation: slideIn 0.3s ease-out 0.35s backwards; }
        .admin-menu-divider { animation: slideIn 0.3s ease-out 0.38s backwards; }
        .admin-auth-button { animation: slideIn 0.3s ease-out 0.41s backwards; }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* 메뉴 타이틀 */
        .menu-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        /* 구분선 */
        .admin-menu-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(148, 163, 184, 0.3), transparent);
            margin: 6px 0;
        }

        /* 로그아웃 버튼 */
        .admin-auth-button {
            display: block;
            padding: 8px 14px;
            margin: 3px 0;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.15));
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            color: #f87171;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            position: relative;
            overflow: hidden;
        }

        .admin-auth-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(239, 68, 68, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .admin-auth-button:active::before {
            width: 300px;
            height: 300px;
        }

        .admin-auth-button:hover {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.25), rgba(220, 38, 38, 0.25));
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 5px 20px rgba(239, 68, 68, 0.3);
            transform: translateX(5px);
        }

        .auth-button-inner {
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            z-index: 1;
        }

        /* hamburger-icon 스타일은 header.html에서 정의됨 */
        `;
    },

    // 팝업 메뉴 초기화
    init: function(hamburgerElement) {
        // 메뉴 HTML 삽입
        const menuContainer = document.createElement('div');
        menuContainer.innerHTML = this.getMenuHTML();
        document.body.appendChild(menuContainer.firstElementChild);

        // CSS 삽입
        const style = document.createElement('style');
        style.textContent = this.getMenuCSS();
        document.head.appendChild(style);

        // 이벤트 리스너 설정
        this.setupEventListeners(hamburgerElement);
    },

    // 이벤트 리스너 설정
    setupEventListeners: function(hamburgerElement) {
        const popupMenu = document.getElementById('adminPopupMenu');
        const logoutButton = document.getElementById('adminLogoutButton');

        if (!popupMenu || !hamburgerElement) return;

        // 햄버거 클릭 시 메뉴 토글
        hamburgerElement.addEventListener('click', (e) => {
            e.stopPropagation();
            popupMenu.classList.toggle('show');
            hamburgerElement.classList.toggle('active');
        });

        // 로그아웃 버튼 클릭
        if (logoutButton) {
            logoutButton.addEventListener('click', async (e) => {
                e.preventDefault();
                e.stopPropagation();

                if (confirm('로그아웃 하시겠습니까?')) {
                    try {
                        // API 경로는 상대 경로 사용
                        const apiPath = window.location.pathname.includes('/pages/') ? '../api/auth.php' : 'api/auth.php';
                        await fetch(`${apiPath}?action=logout`, { method: 'POST' });
                    } catch (error) {
                        console.error('Logout error:', error);
                    }
                    // 로그아웃 후 무조건 로그인 페이지로 이동
                    window.location.href = '/admin/login.php';
                }
            });
        }

        // 메뉴 외부 클릭 시 닫기
        document.addEventListener('click', (e) => {
            if (!popupMenu.contains(e.target) && e.target !== hamburgerElement && !hamburgerElement.contains(e.target)) {
                popupMenu.classList.remove('show');
                hamburgerElement.classList.remove('active');
            }
        });

        // ESC 키로 메뉴 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && popupMenu.classList.contains('show')) {
                popupMenu.classList.remove('show');
                hamburgerElement.classList.remove('active');
            }
        });
    }
};

// 전역으로 export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminPopupMenu;
}
