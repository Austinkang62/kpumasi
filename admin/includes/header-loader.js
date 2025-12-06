// Admin Header Loader
async function loadAdminHeader() {
    console.log('🔄 loadAdminHeader called');
    const headerContainer = document.getElementById('admin-header');
    console.log('admin-header element found?', !!headerContainer);

    if (headerContainer) {
        try {
            // 현재 경로 기준으로 header.html 경로 계산
            const currentPath = window.location.pathname;
            let headerPath = '';
            let jsPath = '';

            if (currentPath.includes('/admin/pages/')) {
                headerPath = '../includes/header.html';
                jsPath = '../js/popup-menu.js';
            } else if (currentPath.includes('/admin/')) {
                headerPath = 'includes/header.html';
                jsPath = 'js/popup-menu.js';
            } else {
                headerPath = '/admin/includes/header.html';
                jsPath = '/admin/js/popup-menu.js';
            }

            const response = await fetch(headerPath);
            if (!response.ok) {
                throw new Error('Header not found: ' + headerPath);
            }
            const html = await response.text();
            headerContainer.innerHTML = html;

            console.log('Header HTML loaded successfully');
            console.log('AdminPopupMenu available?', typeof AdminPopupMenu);

            // 전역 초기화 함수 정의
            window.initAdminPopupMenu = function() {
                const hamburger = document.getElementById('adminHamburger');
                console.log('Attempting to initialize popup menu', {
                    hamburger: !!hamburger,
                    AdminPopupMenu: typeof AdminPopupMenu
                });

                if (hamburger) {
                    if (typeof AdminPopupMenu !== 'undefined') {
                        AdminPopupMenu.init(hamburger);
                        console.log('✅ Popup menu initialized successfully');
                    } else {
                        console.error('❌ AdminPopupMenu is not defined');
                    }
                } else {
                    console.error('❌ Hamburger element not found');
                }
            };

            // header.html 내부의 스크립트 실행
            const scripts = headerContainer.querySelectorAll('script');
            console.log('Found scripts in header:', scripts.length);

            scripts.forEach((script, index) => {
                const newScript = document.createElement('script');
                if (script.src) {
                    newScript.src = script.src;
                    console.log(`Loading external script ${index}:`, script.src);
                } else {
                    newScript.textContent = script.textContent;
                    console.log(`Executing inline script ${index}`);
                }
                document.body.appendChild(newScript);
            });

            // 스크립트 실행 후 초기화
            console.log('Setting up initialization timeout...');
            setTimeout(() => {
                console.log('Timeout triggered, checking initAdminPopupMenu...');
                if (typeof window.initAdminPopupMenu === 'function') {
                    console.log('Calling initAdminPopupMenu...');
                    window.initAdminPopupMenu();
                } else {
                    console.error('initAdminPopupMenu not available!');
                }
            }, 300);

        } catch (error) {
            console.error('Failed to load header:', error);
        }
    }
}

// DOMContentLoaded 또는 즉시 실행
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadAdminHeader);
} else {
    // DOM이 이미 로드된 경우 즉시 실행
    loadAdminHeader();
}

// 전역으로 export (동적 로딩 지원)
window.loadAdminHeader = loadAdminHeader;
