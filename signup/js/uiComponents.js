/**
 * UI Components Module
 * 알림, 팝업, 유틸리티 UI 함수들을 관리
 */

// 알림 표시
export function showAlert(message, type) {
    const alert = document.getElementById('alertMessage');
    alert.textContent = message;
    alert.className = 'alert alert-' + type;
    alert.style.display = 'block';

    setTimeout(() => {
        alert.style.display = 'none';
    }, 5000);
}

// 회원가입 성공 팝업
export function showSuccessPopup(userId, email, accountCount = 1, allAccounts = []) {
    // 다중 계정 목록 HTML 생성
    let accountListHTML = '';
    if (accountCount > 1) {
        accountListHTML = `
            <div style="margin-bottom: 20px; padding: 18px; background: rgba(255, 255, 255, 0.04); border-radius: 12px; border: 1px solid rgba(212, 175, 55, 0.2);">
                <div style="font-size: 12px; color: var(--we1-text-secondary); margin-bottom: 12px; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; text-align: center;">
                    생성된 전체 계정 (${accountCount}개)
                </div>
                <div style="max-height: 180px; overflow-y: auto;">
                    ${allAccounts.map((acc, index) => `
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; margin: 6px 0; background: ${index === 0 ? 'rgba(212, 175, 55, 0.15)' : 'rgba(255, 255, 255, 0.03)'}; border-radius: 8px; border: 1px solid ${index === 0 ? 'var(--we1-gold)' : 'rgba(212, 175, 55, 0.2)'};">
                            <div>
                                <span style="font-size: 10px; color: var(--we1-text-secondary); margin-right: 8px;">${index === 0 ? '👑 대표' : `#${index + 1}`}</span>
                                <span style="font-family: 'Courier New', monospace; font-weight: 700; color: ${index === 0 ? 'var(--we1-gold)' : 'var(--we1-text-primary)'}; font-size: 14px;">${acc}</span>
                            </div>
                            <button onclick="window.signupApp.copyToClipboard('${acc}')" style="background: rgba(212, 175, 55, 0.2); color: var(--we1-gold); border: 1px solid rgba(212, 175, 55, 0.4); padding: 4px 10px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 600;">복사</button>
                        </div>
                    `).join('')}
                </div>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(212, 175, 55, 0.2); font-size: 11px; color: var(--we1-text-secondary); text-align: center; line-height: 1.6;">
                    💡 모든 계정이 <strong style="color: var(--we1-gold);">${allAccounts[0]}</strong>을 정점으로 바이너리 트리 구조로 배치되었습니다
                </div>
            </div>
        `;
    }

    // 오버레이와 팝업 HTML 생성
    const popupHTML = `
        <div id="successOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); display: flex; align-items: center; justify-content: center; z-index: 10000; animation: fadeIn 0.3s; overflow-y: auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 24px; padding: 50px 40px; max-width: 520px; width: 90%; box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6); border: 2px solid var(--we1-gold); position: relative; animation: slideUp 0.4s; margin: auto;">

                <!-- 성공 아이콘 -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #10B981, #059669); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 40px rgba(16, 185, 129, 0.4); animation: bounceIn 0.6s;">
                        <span style="font-size: 60px; color: white; font-weight: bold;">✓</span>
                    </div>
                </div>

                <!-- 타이틀 -->
                <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 800; background: var(--we1-gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-align: center; margin-bottom: 8px;">
                    회원가입 완료!
                </h2>
                <p style="text-align: center; color: var(--we1-text-secondary); font-size: 15px; margin-bottom: 35px; line-height: 1.5;">
                    환영합니다! 🎉<br>${accountCount > 1 ? `<strong style="color: var(--we1-gold);">${accountCount}개</strong>의 계정이 생성되었습니다` : '회원코드가 생성되었습니다'}
                </p>

                <!-- 대표 회원코드 박스 -->
                <div style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(212, 175, 55, 0.05)); border: 2px solid var(--we1-gold); border-radius: 16px; padding: 25px; margin-bottom: 20px; box-shadow: 0 8px 25px rgba(212, 175, 55, 0.15);">
                    <div style="text-align: center;">
                        <div style="font-size: 11px; color: var(--we1-text-secondary); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;">
                            ${accountCount > 1 ? '👑 대표 계정 아이디' : '로그인 아이디'}
                        </div>
                        <div style="font-size: 42px; font-weight: 900; font-family: 'Courier New', monospace; color: var(--we1-gold); letter-spacing: 4px; margin-bottom: 18px; text-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);">
                            ${userId}
                        </div>
                        <button onclick="window.signupApp.copyUserId('${userId}')" style="background: var(--we1-gradient-primary); color: var(--we1-navy-deep); border: none; padding: 12px 28px; border-radius: 10px; font-weight: 700; cursor: pointer; font-size: 14px; transition: all 0.2s; box-shadow: 0 4px 15px rgba(212, 175, 55, 0.3);">
                            📋 아이디 복사
                        </button>
                    </div>
                </div>

                <!-- 다중 계정 목록 -->
                ${accountListHTML}

                <!-- 이메일 정보 -->
                <div style="text-align: center; margin-bottom: 25px; padding: 14px; background: rgba(255, 255, 255, 0.04); border-radius: 10px; border: 1px solid rgba(212, 175, 55, 0.2);">
                    <div style="font-size: 11px; color: var(--we1-text-secondary); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 1px;">등록 이메일</div>
                    <div style="font-size: 15px; color: var(--we1-text-primary); font-weight: 600;">${email}</div>
                </div>

                <!-- 안내 메시지 -->
                <div style="background: rgba(239, 68, 68, 0.1); border-left: 4px solid #EF4444; padding: 14px 18px; border-radius: 8px; margin-bottom: 30px;">
                    <div style="font-size: 13px; color: #FCA5A5; line-height: 1.7;">
                        <strong style="display: block; margin-bottom: 6px;">⚠️ 중요 안내</strong>
                        위의 <strong style="color: var(--we1-gold);">${accountCount > 1 ? '대표 ' : ''}${userId}</strong> 아이디를 <strong>반드시 저장</strong>하세요!<br>
                        로그인 시 이메일이 아닌 <strong>아이디</strong>를 사용합니다.
                    </div>
                </div>

                <!-- 로그인 버튼 -->
                <button onclick="window.signupApp.goToLoginWithId('${userId}')" style="width: 100%; background: var(--we1-gradient-primary); color: var(--we1-navy-deep); border: none; padding: 18px; border-radius: 14px; font-size: 17px; font-weight: 800; cursor: pointer; transition: all 0.3s; box-shadow: 0 6px 25px rgba(212, 175, 55, 0.4); font-family: 'Inter', sans-serif;">
                    로그인 페이지로 이동 →
                </button>

                <div style="text-align: center; margin-top: 15px; font-size: 12px; color: var(--we1-text-secondary);">
                    아이디가 자동으로 입력됩니다
                </div>
            </div>
        </div>

        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from { transform: translateY(40px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @keyframes bounceIn {
                0% { transform: scale(0); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
        </style>
    `;

    // body에 추가
    document.body.insertAdjacentHTML('beforeend', popupHTML);
}

// 가입신청 성공 팝업 (승인 대기)
export function showPendingSuccessPopup(email) {
    const popupHTML = `
        <div id="successOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); display: flex; align-items: center; justify-content: center; z-index: 10000; animation: fadeIn 0.3s; overflow-y: auto; padding: 20px;">
            <div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border-radius: 24px; padding: 50px 40px; max-width: 520px; width: 90%; box-shadow: 0 25px 80px rgba(0, 0, 0, 0.6); border: 2px solid var(--we1-gold); position: relative; animation: slideUp 0.4s; margin: auto;">

                <!-- 성공 아이콘 -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div style="width: 100px; height: 100px; background: linear-gradient(135deg, #f59e0b, #d97706); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 40px rgba(245, 158, 11, 0.4); animation: bounceIn 0.6s;">
                        <span style="font-size: 50px; color: white;">📝</span>
                    </div>
                </div>

                <!-- 타이틀 -->
                <h2 style="font-family: 'Playfair Display', serif; font-size: 32px; font-weight: 800; background: var(--we1-gradient-primary); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-align: center; margin-bottom: 8px;">
                    가입신청 완료!
                </h2>
                <p style="text-align: center; color: var(--we1-text-secondary); font-size: 15px; margin-bottom: 35px; line-height: 1.5;">
                    신청이 접수되었습니다. 🎉<br>관리자 승인 후 가입이 완료됩니다.
                </p>

                <!-- 이메일 정보 -->
                <div style="background: linear-gradient(135deg, rgba(212, 175, 55, 0.2), rgba(212, 175, 55, 0.05)); border: 2px solid var(--we1-gold); border-radius: 16px; padding: 25px; margin-bottom: 20px; box-shadow: 0 8px 25px rgba(212, 175, 55, 0.15);">
                    <div style="text-align: center;">
                        <div style="font-size: 11px; color: var(--we1-text-secondary); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 2px; font-weight: 600;">
                            신청 이메일
                        </div>
                        <div style="font-size: 20px; font-weight: 700; font-family: 'Inter', sans-serif; color: var(--we1-gold); margin-bottom: 10px;">
                            ${email}
                        </div>
                    </div>
                </div>

                <!-- 안내 사항 -->
                <div style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3B82F6; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="font-size: 13px; color: #93C5FD; line-height: 1.7;">
                        <strong style="display: block; margin-bottom: 6px;">📋 다음 단계</strong>
                        1. 관리자가 TXID를 확인합니다<br>
                        2. 승인되면 이메일로 회원코드가 발송됩니다<br>
                        3. 회원코드로 로그인하시면 됩니다
                    </div>
                </div>

                <!-- 예상 시간 -->
                <div style="text-align: center; margin-bottom: 25px; padding: 14px; background: rgba(255, 255, 255, 0.04); border-radius: 10px; border: 1px solid rgba(212, 175, 55, 0.2);">
                    <div style="font-size: 11px; color: var(--we1-text-secondary); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 1px;">예상 처리 시간</div>
                    <div style="font-size: 16px; color: var(--we1-gold); font-weight: 700;">24시간 이내</div>
                </div>

                <!-- 홈으로 버튼 -->
                <button onclick="window.location.href='../index.html'" style="width: 100%; background: var(--we1-gradient-primary); color: var(--we1-navy-deep); border: none; padding: 18px; border-radius: 14px; font-size: 17px; font-weight: 800; cursor: pointer; transition: all 0.3s; box-shadow: 0 6px 25px rgba(212, 175, 55, 0.4); font-family: 'Inter', sans-serif;">
                    홈으로 돌아가기
                </button>

                <div style="text-align: center; margin-top: 15px; font-size: 12px; color: var(--we1-text-secondary);">
                    승인 완료 시 이메일로 알림을 보내드립니다
                </div>
            </div>
        </div>

        <style>
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideUp {
                from { transform: translateY(40px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            @keyframes bounceIn {
                0% { transform: scale(0); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
        </style>
    `;

    document.body.insertAdjacentHTML('beforeend', popupHTML);
}

// 아이디 복사 함수
export function copyUserId(userId) {
    navigator.clipboard.writeText(userId).then(() => {
        const btn = event.target;
        const originalText = btn.textContent;

        btn.textContent = '✓ 복사 완료!';
        btn.style.background = 'linear-gradient(135deg, #10B981, #059669)';
        btn.style.color = 'white';

        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = 'var(--we1-gradient-primary)';
            btn.style.color = 'var(--we1-navy-deep)';
        }, 2000);
    }).catch(err => {
        alert('복사에 실패했습니다. 아이디를 수동으로 복사해주세요.');
    });
}

// 클립보드 복사 (다중 계정용)
export function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const originalText = btn.textContent;

        btn.textContent = '✓';
        btn.style.background = 'rgba(16, 185, 129, 0.3)';
        btn.style.borderColor = '#10B981';

        setTimeout(() => {
            btn.textContent = originalText;
            btn.style.background = 'rgba(212, 175, 55, 0.2)';
            btn.style.borderColor = 'rgba(212, 175, 55, 0.4)';
        }, 1500);
    }).catch(err => {
        alert('복사에 실패했습니다.');
    });
}

// 로그인 페이지로 아이디와 함께 이동
export function goToLoginWithId(userId) {
    // URL 파라미터로 아이디 전달
    window.location.href = `login.html?id=${encodeURIComponent(userId)}`;
}

// 파티클 생성
export function createParticles() {
    const container = document.getElementById('particles');
    if (!container) return;

    const particleCount = 25;
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.top = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 3 + 's';
        particle.style.animationDuration = (Math.random() * 3 + 2) + 's';
        container.appendChild(particle);
    }
}

// 점검 모드 화면 표시
export function showMaintenanceMode() {
    const signupContainer = document.querySelector('.signup-container');

    signupContainer.innerHTML = `
        <div class="signup-card" style="text-align: center; padding: 80px 50px;">
            <!-- 점검 아이콘 -->
            <div style="margin-bottom: 30px;">
                <div style="width: 120px; height: 120px; background: linear-gradient(135deg, #F59E0B, #EF4444); border-radius: 50%; margin: 0 auto; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 40px rgba(245, 158, 11, 0.4); animation: pulse 2s infinite;">
                    <span style="font-size: 70px;">🔧</span>
                </div>
            </div>

            <!-- 제목 -->
            <h1 class="form-title" style="font-size: 32px; margin-bottom: 15px;">
                시스템 점검 중
            </h1>

            <div class="gold-line" style="margin: 25px auto;"></div>

            <!-- 메시지 -->
            <p style="font-size: 16px; color: var(--we1-text-secondary); line-height: 1.8; margin-bottom: 25px;">
                더 나은 서비스 제공을 위해<br>
                <strong style="color: var(--we1-gold);">회원가입 기능</strong>을 일시적으로 중단하였습니다.
            </p>

            <!-- 안내 박스 -->
            <div style="background: rgba(212, 175, 55, 0.1); border: 2px solid rgba(212, 175, 55, 0.3); border-radius: 16px; padding: 25px; margin: 30px 0;">
                <div style="font-size: 14px; color: var(--we1-text-primary); line-height: 1.8;">
                    <strong style="color: var(--we1-gold); font-size: 15px;">📋 점검 사유</strong><br>
                    <span style="color: var(--we1-text-secondary);">
                        • 시스템 업그레이드 및 보안 강화<br>
                        • 회원가입 프로세스 개선<br>
                        • 안정성 향상 작업
                    </span>
                </div>
            </div>

            <!-- 예상 시간 -->
            <div style="margin: 25px 0; padding: 15px; background: rgba(59, 130, 246, 0.1); border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.3);">
                <div style="font-size: 13px; color: #93C5FD; margin-bottom: 5px;">⏰ 예상 완료 시간</div>
                <div style="font-size: 16px; font-weight: 700; color: #60A5FA;">2025년 11월 12일 13:00</div>
            </div>

            <!-- 홈 버튼 -->
            <a href="../index.html" style="display: inline-block; margin-top: 30px; padding: 16px 40px; background: var(--we1-gradient-primary); color: var(--we1-navy-deep); border-radius: 12px; font-weight: 700; text-decoration: none; transition: all 0.3s; box-shadow: 0 4px 20px rgba(212, 175, 55, 0.3);">
                🏠 홈으로 돌아가기
            </a>

            <!-- 하단 안내 -->
            <p style="margin-top: 30px; font-size: 13px; color: var(--we1-text-muted);">
                불편을 드려 죄송합니다. 빠른 시일 내에 정상화하겠습니다.
            </p>
        </div>

        <style>
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        </style>
    `;
}
