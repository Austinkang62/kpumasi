// ===================================================================
// NEURAL PULSE NETWORK - Unified Popup Menu Component
// ===================================================================
// 이 파일은 모든 페이지에서 공통으로 사용하는 팝업 메뉴를 제공합니다.
// ===================================================================

const NeuralPopupMenu = {
    // 국기 SVG 맵
    flagMap: {
        'en': `<svg width="128" height="128" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="circle-en"><circle cx="64" cy="64" r="64"/></clipPath></defs><g clip-path="url(#circle-en)"><rect width="128" height="128" fill="#B22234"/><path d="M0 10h128M0 29.5h128M0 49h128M0 68.5h128M0 88h128M0 107.5h128" stroke="#FFF" stroke-width="9.8"/><rect width="52" height="56" fill="#3C3B6E"/><g fill="#FFF"><circle cx="8.8" cy="8" r="2.4"/><circle cx="26" cy="8" r="2.4"/><circle cx="43.2" cy="8" r="2.4"/><circle cx="17.2" cy="18.8" r="2.4"/><circle cx="34.4" cy="18.8" r="2.4"/><circle cx="8.8" cy="29.6" r="2.4"/><circle cx="26" cy="29.6" r="2.4"/><circle cx="43.2" cy="29.6" r="2.4"/><circle cx="17.2" cy="40.4" r="2.4"/><circle cx="34.4" cy="40.4" r="2.4"/></g></g></svg>`,
        'ko': `<svg width="128" height="128" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="circle-ko"><circle cx="64" cy="64" r="64"/></clipPath></defs><g clip-path="url(#circle-ko)"><rect width="128" height="128" fill="#FFF"/><circle cx="64" cy="64" r="22" fill="#CD2E3A"/><path d="M64 42 A22 22 0 0 1 64 86 A11 11 0 0 1 64 64 A11 11 0 0 0 64 42 Z" fill="#0047A0"/><circle cx="64" cy="53" r="11" fill="#CD2E3A"/><circle cx="64" cy="75" r="11" fill="#0047A0"/><g fill="#000"><rect x="84" y="28" width="24" height="3.5"/><rect x="84" y="38" width="24" height="3.5"/><rect x="84" y="48" width="24" height="3.5"/></g><g fill="#000"><rect x="20" y="76" width="10" height="3.5"/><rect x="34" y="76" width="10" height="3.5"/><rect x="20" y="86" width="10" height="3.5"/><rect x="34" y="86" width="10" height="3.5"/><rect x="20" y="96" width="10" height="3.5"/><rect x="34" y="96" width="10" height="3.5"/></g><g fill="#000"><rect x="20" y="28" width="24" height="3.5"/><rect x="20" y="38" width="10" height="3.5"/><rect x="34" y="38" width="10" height="3.5"/><rect x="20" y="48" width="24" height="3.5"/></g><g fill="#000"><rect x="84" y="76" width="10" height="3.5"/><rect x="98" y="76" width="10" height="3.5"/><rect x="84" y="86" width="24" height="3.5"/><rect x="84" y="96" width="10" height="3.5"/><rect x="98" y="96" width="10" height="3.5"/></g></g></svg>`,
        'zh': `<svg width="128" height="128" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="circle-zh"><circle cx="64" cy="64" r="64"/></clipPath></defs><g clip-path="url(#circle-zh)"><rect width="128" height="128" fill="#DE2910"/><g fill="#FFDE00"><path d="M32 24l4.8 14.8h15.6l-12.6 9.2 4.8 14.8L32 53.6l-12.4 9.2 4.8-14.8-12.8-9.2h15.6z"/><path d="M64 16l1.6 4.8h5.2l-4.2 3.2 1.6 4.8-4.2-2.8-4.2 2.8 1.6-4.8-4.2-3.2h5.2z" transform="rotate(-5 64 20)"/><path d="M76 30l1.6 4.8h5.2l-4.2 3.2 1.6 4.8-4.2-2.8-4.2 2.8 1.6-4.8-4.2-3.2h5.2z" transform="rotate(-25 76 34)"/><path d="M80 48l1.6 4.8h5.2l-4.2 3.2 1.6 4.8-4.2-2.8-4.2 2.8 1.6-4.8-4.2-3.2h5.2z" transform="rotate(-45 80 52)"/><path d="M72 64l1.6 4.8h5.2l-4.2 3.2 1.6 4.8-4.2-2.8-4.2 2.8 1.6-4.8-4.2-3.2h5.2z" transform="rotate(-65 72 68)"/></g></g></svg>`,
        'vi': `<svg width="128" height="128" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="circle-vi"><circle cx="64" cy="64" r="64"/></clipPath></defs><g clip-path="url(#circle-vi)"><rect width="128" height="128" fill="#DA251D"/><path d="M64 36l8 24.8h26l-21 15.2 8 24.8-21-15.2-21 15.2 8-24.8-21-15.2h26z" fill="#FFFF00"/></g></svg>`,
        'tl': `<svg width="128" height="128" viewBox="0 0 128 128" xmlns="http://www.w3.org/2000/svg"><defs><clipPath id="circle-tl"><circle cx="64" cy="64" r="64"/></clipPath></defs><g clip-path="url(#circle-tl)"><rect width="128" height="128" fill="#0038A8"/><rect y="64" width="128" height="64" fill="#CE1126"/><polygon points="0,0 64,64 0,128" fill="#FFF"/><circle cx="21.3" cy="64" r="10.7" fill="#FCD116"/><g fill="#FCD116"><path d="M21.3 42.7l2 6.2h6.5l-5.3 3.8 2 6.2-5.2-3.8-5.2 3.8 2-6.2-5.3-3.8h6.5z"/><path d="M21.3 53.3l1.4 4.3h4.5l-3.7 2.7 1.4 4.3-3.6-2.6-3.6 2.6 1.4-4.3-3.7-2.7h4.5z"/><path d="M21.3 64l2.8 8.6h9l-7.3 5.3 2.8 8.6-7.3-5.3-7.3 5.3 2.8-8.6-7.3-5.3h9z"/></g><path d="M10.7 21.3l3 3 M10.7 106.7l3-3 M32 21.3l-3 3 M32 106.7l-3-3" stroke="#FCD116" stroke-width="2"/><circle cx="10.7" cy="21.3" r="2.7" fill="#FCD116"/><circle cx="32" cy="21.3" r="2.7" fill="#FCD116"/><circle cx="10.7" cy="106.7" r="2.7" fill="#FCD116"/></g></svg>`
    },

    // 팝업 메뉴 HTML 생성
    getMenuHTML: function() {
        const currentLang = this.getCurrentLanguage();

        return `
        <div class="nav-popup-menu" id="navPopupMenu">
            <!-- Header: Dashboard Button + Language Selector -->
            <div class="nav-header">
                <a href="dashboard.html" class="nav-dashboard-button">
                    <div class="mini-pulse">
                        <div class="mini-ring"></div>
                        <div class="mini-ring"></div>
                        <div class="mini-core"></div>
                    </div>
                </a>

                <!-- Language Selector -->
                <div class="nav-language-selector">
                    <div class="nav-language-current" id="navLanguageCurrent">
                        <div class="nav-language-flag">${this.flagMap[currentLang]}</div>
                    </div>
                    <div class="nav-language-dropdown" id="navLanguageDropdown">
                        ${Object.keys(this.flagMap).map(lang => `
                            <div class="nav-language-option ${lang === currentLang ? 'active' : ''}" data-langcode="${lang}">
                                <div class="nav-language-flag">${this.flagMap[lang]}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>

            <!-- ORG Chart -->
            <a href="organization.html" class="nav-menu-item nav-menu-link" data-menu="org-chart">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="nav-menu-icon"></div>
                        <span>ORG Chart</span>
                    </div>
                </div>
            </a>

            <!-- Membership -->
            <div class="nav-menu-item" data-menu="membership">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="nav-menu-icon"></div>
                        <span>Membership</span>
                    </div>
                    <span class="menu-arrow">▼</span>
                </div>
                <div class="submenu">
                    <div class="submenu-item" data-action="signup" data-url="signup.html">Sign Up</div>
                    <div class="submenu-item" data-action="edit-profile" data-url="edit-profile.html">Edit Profile</div>
                    <div class="submenu-item" data-action="referral-link" data-url="referral-link.html">Referral Link</div>
                </div>
            </div>

            <!-- Purchase -->
            <div class="nav-menu-item" data-menu="purchase">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="nav-menu-icon"></div>
                        <span>Purchase</span>
                    </div>
                    <span class="menu-arrow">▼</span>
                </div>
                <div class="submenu">
                    <div class="submenu-item" data-action="package" data-url="purchase.html">Package</div>
                    <div class="submenu-item" data-action="avatar" data-url="purchase-avatar.html">Avatar</div>
                    <div class="submenu-item" data-action="purchase-list" data-url="purchase-list.html">Purchase List</div>
                </div>
            </div>

            <!-- Bonus -->
            <div class="nav-menu-item" data-menu="bonus">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="nav-menu-icon"></div>
                        <span>Bonus</span>
                    </div>
                    <span class="menu-arrow">▼</span>
                </div>
                <div class="submenu">
                    <div class="submenu-item" data-action="bonus-guide" data-url="bonus_guide.html">Bonus Guide</div>
                    <div class="submenu-item" data-action="bonus-list" data-url="bonus-list.html">Bonus List</div>
                </div>
            </div>

            <!-- Withdrawal -->
            <div class="nav-menu-item" data-menu="withdrawal">
                <div class="menu-title">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="nav-menu-icon"></div>
                        <span>Withdrawal</span>
                    </div>
                    <span class="menu-arrow">▼</span>
                </div>
                <div class="submenu">
                    <div class="submenu-item" data-action="withdrawal" data-url="withdrawal.html">Withdrawal</div>
                    <div class="submenu-item" data-action="withdrawal-list" data-url="withdrawal-list.html">Withdrawal List</div>
                </div>
            </div>

            <!-- Login/Logout Button -->
            <div class="nav-menu-divider"></div>
            <a href="#" class="nav-auth-button" id="navAuthButton">
                <div class="auth-button-inner">
                    <div class="nav-menu-icon"></div>
                    <span id="navAuthButtonText">Login</span>
                </div>
            </a>
        </div>
        `;
    },

    // 팝업 메뉴 CSS 생성
    getMenuCSS: function() {
        return `
        /* 네비게이션 팝업 메뉴 */
        .nav-popup-menu {
            position: fixed;
            top: 70px;
            left: 50%;
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.1), rgba(255, 0, 255, 0.1));
            backdrop-filter: blur(30px);
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 20px;
            padding: 12px;
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transform: translateX(-50%) translateY(-20px) scale(0.9);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 20px 60px rgba(0, 255, 255, 0.2);
            min-width: 200px;
            overflow: visible;
        }

        .nav-popup-menu.show {
            opacity: 1;
            visibility: visible;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0) scale(1);
        }

        .nav-menu-item {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0;
            padding: 12px 20px;
            margin: 5px 0;
            background: rgba(0, 255, 255, 0.05);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 12px;
            color: #fff;
            text-decoration: none;
            font-size: 14px;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: visible;
        }


        .nav-menu-link {
            display: block;
            text-decoration: none;
        }

        .nav-menu-link .menu-title {
            pointer-events: none;
        }

        .nav-menu-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(90deg, rgba(0, 255, 255, 0.2), rgba(255, 0, 255, 0.2));
            transition: width 0.4s;
            z-index: -1;
        }

        .nav-menu-item:hover::before {
            width: 100%;
        }

        .nav-menu-item:hover {
            border-color: #00ffff;
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0, 255, 255, 0.3);
        }

        .nav-menu-icon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #00ffff;
            box-shadow: 0 0 10px #00ffff;
            animation: iconPulse 2s ease-in-out infinite;
        }

        @keyframes iconPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        .nav-header { animation: slideIn 0.3s ease-out 0.05s backwards; }
        .nav-menu-item:nth-child(2) { animation: slideIn 0.3s ease-out 0.1s backwards; }
        .nav-menu-item:nth-child(3) { animation: slideIn 0.3s ease-out 0.15s backwards; }
        .nav-menu-item:nth-child(4) { animation: slideIn 0.3s ease-out 0.2s backwards; }
        .nav-menu-item:nth-child(5) { animation: slideIn 0.3s ease-out 0.25s backwards; }
        .nav-menu-item:nth-child(6) { animation: slideIn 0.3s ease-out 0.3s backwards; }
        .nav-menu-divider { animation: slideIn 0.3s ease-out 0.35s backwards; }
        .nav-auth-button { animation: slideIn 0.3s ease-out 0.4s backwards; }
        

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

        .menu-arrow {
            font-size: 12px;
            transition: transform 0.3s;
            position: absolute;
            right: 20px;
        }

        .nav-menu-item.expanded .menu-arrow {
            transform: rotate(180deg);
        }

        /* 서브메뉴 */
        .submenu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 0;
        }

        .submenu.show {
            max-height: 500px;
        }

        .submenu-item {
            padding: 10px 15px 10px 30px;
            margin: 3px 0;
            background: rgba(0, 255, 255, 0.03);
            border-left: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 8px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submenu-item:hover {
            background: rgba(0, 255, 255, 0.1);
            border-left-color: #00ffff;
            color: #00ffff;
            padding-left: 35px;
        }

        /* 구분선 */
        .nav-menu-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.3), transparent);
            margin: 15px 10px;
        }

        /* 로그인/로그아웃 버튼 */
        .nav-auth-button {
            display: block;
            padding: 12px 20px;
            margin: 5px 0;
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.15), rgba(255, 0, 255, 0.15));
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 12px;
            color: #00ffff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 1.5px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .nav-auth-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(0, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .nav-auth-button:active::before {
            width: 300px;
            height: 300px;
        }

        .nav-auth-button:hover {
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.25), rgba(255, 0, 255, 0.25));
            border-color: rgba(0, 255, 255, 0.5);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.3);
            transform: translateY(-1px);
        }

        .auth-button-inner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }

        /* Dashboard Button (Mini Pulse) */
        .nav-dashboard-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            text-decoration: none;
            cursor: pointer;
        }

        .mini-pulse {
            position: relative;
            width: 42px;
            height: 42px;
        }

        .mini-ring {
            position: absolute;
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 50%;
            animation: miniRingRotate 3s linear infinite;
        }

        .mini-ring:nth-child(1) {
            width: 100%;
            height: 100%;
            border-top-color: #00ffff;
            animation-duration: 2s;
        }

        .mini-ring:nth-child(2) {
            width: 70%;
            height: 70%;
            top: 15%;
            left: 15%;
            border-right-color: #ff00ff;
            animation-duration: 3s;
            animation-direction: reverse;
        }

        .mini-core {
            position: absolute;
            width: 12px;
            height: 12px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: radial-gradient(circle, #00ffff 0%, transparent 70%);
            border-radius: 50%;
            animation: miniCorePulse 1.5s ease-in-out infinite;
        }

        @keyframes miniRingRotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes miniCorePulse {
            0%, 100% {
                box-shadow: 0 0 10px #00ffff;
            }
            50% {
                box-shadow: 0 0 20px #00ffff, 0 0 30px #00ffff;
            }
        }

        .nav-dashboard-button:hover .mini-pulse {
            transform: scale(1.1);
            transition: transform 0.3s;
        }

        /* Header: Dashboard + Language Selector */
        .nav-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 12px;
            padding: 8px 0;
        }

        /* Language Selector */
        .nav-language-selector {
            position: relative;
        }

        .nav-language-current {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border-radius: 50%;
            overflow: hidden;
        }

        .nav-language-current:hover {
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.5), 0 0 40px rgba(0, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .nav-language-current.active {
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.6);
        }

        .nav-language-flag {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            overflow: hidden;
        }

        .nav-language-flag svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .nav-language-dropdown {
            position: absolute;
            top: 50px;
            right: 0;
            background: rgba(10, 10, 31, 0.98);
            backdrop-filter: blur(30px);
            border: 2px solid rgba(0, 255, 255, 0.5);
            border-radius: 15px;
            padding: 6px;
            min-width: 55px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            z-index: 10001;
        }

        .nav-language-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .nav-language-option {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 6px;
            margin: 3px 0;
            background: rgba(0, 255, 255, 0.05);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            width: 38px;
            height: 38px;
        }

        .nav-language-option:hover {
            background: rgba(0, 255, 255, 0.1);
            transform: scale(1.15);
            box-shadow: 0 0 15px rgba(0, 255, 255, 0.4);
        }

        .nav-language-option.active {
            background: rgba(0, 255, 255, 0.15);
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.6), 0 0 10px rgba(0, 255, 255, 0.3) inset;
        }
        `;
    },

    // 팝업 메뉴 초기화
    init: function(pulseElement) {
        // 메뉴 HTML 삽입
        const menuContainer = document.createElement('div');
        menuContainer.innerHTML = this.getMenuHTML();
        document.body.appendChild(menuContainer.firstElementChild);

        // CSS 삽입
        const style = document.createElement('style');
        style.textContent = this.getMenuCSS();
        document.head.appendChild(style);

        // 로그인 상태 업데이트
        this.updateAuthButton();

        // 이벤트 리스너 설정
        this.setupEventListeners(pulseElement);
    },

    // 현재 언어 가져오기
    getCurrentLanguage: function() {
        return localStorage.getItem('selectedLanguage') || 'ko';
    },

    // 언어 변경
    changeLanguage: function(lang) {
        localStorage.setItem('selectedLanguage', lang);

        // LanguageManager가 있으면 사용
        if (window.langManager && window.langManager.switchLanguage) {
            window.langManager.switchLanguage(lang);
        } else {
            // LanguageManager가 없으면 직접 이벤트 발생
            document.dispatchEvent(new CustomEvent('languageChanged', { detail: { language: lang } }));
        }

        // 언어 선택기 UI 업데이트
        this.updateLanguageUI();
    },

    // 언어 선택기 UI 업데이트
    updateLanguageUI: function() {
        const currentLang = this.getCurrentLanguage();
        const currentFlag = document.querySelector('#navLanguageCurrent .nav-language-flag');
        const options = document.querySelectorAll('.nav-language-option');

        if (currentFlag) {
            currentFlag.innerHTML = this.flagMap[currentLang] || this.flagMap['ko'];
        }

        options.forEach(opt => {
            if (opt.dataset.langcode === currentLang) {
                opt.classList.add('active');
            } else {
                opt.classList.remove('active');
            }
        });
    },

    // 로그인 상태에 따라 버튼 업데이트
    updateAuthButton: function() {
        const authButton = document.getElementById('navAuthButton');
        const authButtonText = document.getElementById('navAuthButtonText');

        if (!authButton || !authButtonText) return;

        // localStorage에서 세션 토큰 확인
        const isLoggedIn = localStorage.getItem('neural_token') !== null;

        if (isLoggedIn) {
            // 로그인 상태
            authButtonText.textContent = 'Logout';
            authButton.href = 'logout.html';
        } else {
            // 로그아웃 상태
            authButtonText.textContent = 'Login';
            authButton.href = 'login.html';
        }
    },

    // 이벤트 리스너 설정
    setupEventListeners: function(pulseElement) {
        const popupMenu = document.getElementById('navPopupMenu');

        if (!popupMenu || !pulseElement) return;

        // 펄스 클릭 시 메뉴 토글
        pulseElement.addEventListener('click', (e) => {
            e.stopPropagation();
            popupMenu.classList.toggle('show');
        });

        // 언어 선택기 이벤트
        const langCurrent = document.getElementById('navLanguageCurrent');
        const langDropdown = document.getElementById('navLanguageDropdown');
        const langOptions = document.querySelectorAll('.nav-language-option');

        if (langCurrent && langDropdown) {
            // 언어 버튼 클릭 시 드롭다운 토글
            langCurrent.addEventListener('click', (e) => {
                e.stopPropagation();
                langDropdown.classList.toggle('show');
                langCurrent.classList.toggle('active');
            });

            // 언어 선택
            langOptions.forEach(option => {
                option.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const selectedLang = option.dataset.langcode;
                    this.changeLanguage(selectedLang);
                    langDropdown.classList.remove('show');
                    langCurrent.classList.remove('active');

                    // 전체 팝업 메뉴 닫기
                    popupMenu.classList.remove('show');

                    // 모든 서브메뉴 닫기
                    const menuItems = document.querySelectorAll('.nav-menu-item');
                    menuItems.forEach(item => {
                        item.classList.remove('expanded');
                        const submenu = item.querySelector('.submenu');
                        if (submenu) {
                            submenu.classList.remove('show');
                        }
                    });
                });
            });

            // 드롭다운 클릭 시 이벤트 전파 방지
            langDropdown.addEventListener('click', (e) => {
                e.stopPropagation();
            });
        }

        // 메뉴 아이템 클릭 시 서브메뉴 토글
        const menuItems = document.querySelectorAll('.nav-menu-item');
        menuItems.forEach(item => {
            const menuTitle = item.querySelector('.menu-title');
            const submenu = item.querySelector('.submenu');

            if (menuTitle && submenu) {
                menuTitle.addEventListener('click', (e) => {
                    e.stopPropagation();

                    // 다른 메뉴 닫기
                    menuItems.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('expanded');
                            const otherSubmenu = otherItem.querySelector('.submenu');
                            if (otherSubmenu) {
                                otherSubmenu.classList.remove('show');
                            }
                        }
                    });

                    // 현재 메뉴 토글
                    item.classList.toggle('expanded');
                    submenu.classList.toggle('show');
                });
            }
        });

        // 서브메뉴 아이템 클릭 시 페이지 이동
        const submenuItems = document.querySelectorAll('.submenu-item');
        submenuItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const url = item.dataset.url;

                // URL이 있으면 페이지 이동
                if (url) {
                    window.location.href = url;
                }
            });
        });

        // 메뉴 외부 클릭 시 닫기
        document.addEventListener('click', (e) => {
            if (!popupMenu.contains(e.target) && e.target !== pulseElement) {
                popupMenu.classList.remove('show');

                // 모든 서브메뉴 닫기
                menuItems.forEach(item => {
                    item.classList.remove('expanded');
                    const submenu = item.querySelector('.submenu');
                    if (submenu) {
                        submenu.classList.remove('show');
                    }
                });

                // 언어 드롭다운 닫기
                if (langDropdown && langCurrent) {
                    langDropdown.classList.remove('show');
                    langCurrent.classList.remove('active');
                }
            }
        });

        // ESC 키로 메뉴 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && popupMenu.classList.contains('show')) {
                popupMenu.classList.remove('show');

                // 모든 서브메뉴 닫기
                menuItems.forEach(item => {
                    item.classList.remove('expanded');
                    const submenu = item.querySelector('.submenu');
                    if (submenu) {
                        submenu.classList.remove('show');
                    }
                });

                // 언어 드롭다운 닫기
                if (langDropdown && langCurrent) {
                    langDropdown.classList.remove('show');
                    langCurrent.classList.remove('active');
                }
            }
        });
    }
};

// 전역으로 export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NeuralPopupMenu;
}
