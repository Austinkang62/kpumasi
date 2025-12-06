<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>보너스 검증 - K-Pumasi Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            padding: 5px;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 5px;
        }

        .header {
            display: none;
        }

        h1 {
            color: #f8fafc;
            font-size: 2em;
            font-weight: 700;
        }

        .back-button {
            padding: 10px 20px;
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }

        .back-button:hover {
            background: rgba(59, 130, 246, 0.3);
            transform: translateX(-5px);
        }

        .verification-container {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
            padding: 10px 5px;
        }

        .loading {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
            font-size: 1.1em;
        }

        .verification-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 0;
        }

        @media (max-width: 1400px) {
            .verification-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .verification-grid {
                grid-template-columns: 1fr;
            }
        }

        .verification-card {
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid;
            border-radius: 12px;
            padding: 20px;
        }

        .verification-card.referral { border-color: #60a5fa; }
        .verification-card.edge { border-color: #10b981; }
        .verification-card.matching { border-color: #fbbf24; }
        .verification-card.rollup { border-color: #a78bfa; }

        .card-header {
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }

        .card-header.referral { color: #60a5fa; }
        .card-header.edge { color: #10b981; }
        .card-header.matching { color: #fbbf24; }
        .card-header.rollup { color: #a78bfa; }

        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        }

        .stat-label {
            color: #94a3b8;
            font-size: 0.95em;
        }

        .stat-value {
            color: #f8fafc;
            font-weight: 600;
        }

        .stat-value.ok { color: #10b981; }
        .stat-value.error { color: #ef4444; }

        .details-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid rgba(148, 163, 184, 0.2);
        }

        .detail-item {
            background: rgba(0, 0, 0, 0.2);
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            border-left: 3px solid;
        }

        .detail-item.ok { border-left-color: #10b981; }
        .detail-item.error { border-left-color: #ef4444; }

        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.85em;
            font-weight: 600;
            margin-left: 8px;
        }

        .status-badge.ok {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .status-badge.error {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 id="pageTitle">🔍 보너스 검증</h1>
            <a href="/admin/" class="back-button">← 대시보드</a>
        </div>

        <!-- 회원 검색 -->
        <div class="verification-container" id="searchContainer" style="display: none; text-align: center; padding: 60px 20px;">
            <h2 style="color: #f8fafc; margin-bottom: 20px;">회원 ID를 입력하세요</h2>
            <div style="display: flex; gap: 10px; max-width: 500px; margin: 0 auto;">
                <input type="text" id="userIdInput" placeholder="예: BIAW0618"
                    style="flex: 1; padding: 14px 20px; background: rgba(15, 23, 42, 0.6); border: 2px solid rgba(148, 163, 184, 0.2); border-radius: 10px; color: #f8fafc; font-size: 1em;"
                    onkeypress="if(event.key==='Enter') searchUser()" />
                <button onclick="searchUser()"
                    style="padding: 14px 30px; background: linear-gradient(135deg, #3b82f6, #2563eb); border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; font-size: 1em;">
                    검색
                </button>
            </div>
        </div>

        <div class="verification-container" id="verificationContainer" style="display: none;">
            <div id="verificationContent" class="loading">
                검증 중입니다...
            </div>
        </div>
    </div>

    <script>
        // URL에서 user_id 파라미터 가져오기
        const urlParams = new URLSearchParams(window.location.search);
        const userId = urlParams.get('user_id');

        if (!userId) {
            // user_id가 없으면 검색 UI 표시
            document.getElementById('searchContainer').style.display = 'block';
            document.getElementById('userIdInput').focus();
        } else {
            // user_id가 있으면 검증 실행
            document.getElementById('verificationContainer').style.display = 'block';
            loadAndVerify(userId);
        }

        // 회원 검색
        function searchUser() {
            const input = document.getElementById('userIdInput').value.trim();
            if (!input) {
                alert('회원 ID를 입력하세요.');
                return;
            }
            window.location.href = `?user_id=${encodeURIComponent(input)}`;
        }

        async function loadAndVerify(userId) {
            try {
                // 회원 데이터 로드
                const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(userId)}`);
                const result = await response.json();

                if (!result.success) {
                    document.getElementById('verificationContent').innerHTML =
                        `<div style="color: #ef4444;">회원 정보를 불러올 수 없습니다: ${result.message}</div>`;
                    return;
                }

                // 페이지 제목 업데이트
                document.getElementById('pageTitle').innerHTML = `🔍 보너스 검증 : <span style="color: #60a5fa; font-weight: 700;">${result.member.user_id}</span>`;

                // 검증 실행
                const [referralResults, edgeResults, matchingResults, rollupResults] = await Promise.all([
                    verifyReferralBonuses(result),
                    verifyEdgeBonuses(result),
                    verifyMatchingBonuses(result),
                    Promise.resolve(verifyRollupBonuses(result))
                ]);

                renderResults(result.member, [referralResults, edgeResults, matchingResults, rollupResults]);
            } catch (error) {
                console.error('검증 오류:', error);
                document.getElementById('verificationContent').innerHTML =
                    `<div style="color: #ef4444;">검증 중 오류가 발생했습니다: ${error.message}</div>`;
            }
        }

        // 레퍼럴 보너스 검증
        async function verifyReferralBonuses(data) {
            const m = data.member;
            const referrals = data.referrals || [];
            const receivedReferralBonuses = data.received_bonuses.filter(b => b.bonus_type === 'referral');

            const results = {
                type: 'referral',
                expectedCount: 0,
                actualCount: receivedReferralBonuses.length,
                expectedAmount: 0,
                actualAmount: receivedReferralBonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0),
                missingBonuses: [],
                details: []
            };

            for (const ref of referrals) {
                try {
                    const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(ref.user_id)}`);
                    const refData = await response.json();

                    if (refData.success && refData.sales && refData.sales.length > 0) {
                        const totalSales = refData.sales.reduce((sum, s) => sum + parseFloat(s.amount || 0), 0);
                        const expectedBonus = totalSales * 0.25;

                        const bonusesFromThisRef = receivedReferralBonuses.filter(b => b.giver_code === ref.user_id);
                        const actualBonus = bonusesFromThisRef.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);

                        results.expectedCount += refData.sales.length;
                        results.expectedAmount += expectedBonus;

                        let status = 'OK';
                        if (Math.abs(expectedBonus - actualBonus) > 0.01) {
                            status = 'MISMATCH';
                            results.missingBonuses.push({
                                user_id: ref.user_id,
                                expected: expectedBonus,
                                actual: actualBonus,
                                diff: expectedBonus - actualBonus
                            });
                        }

                        results.details.push({
                            user_id: ref.user_id,
                            name: ref.name,
                            expectedBonus: expectedBonus,
                            actualBonus: actualBonus,
                            status: status
                        });
                    }
                } catch (error) {
                    console.error(`Error verifying ${ref.user_id}:`, error);
                }
            }

            return results;
        }

        // 엣지 보너스 검증
        async function verifyEdgeBonuses(data) {
            const receivedEdgeBonuses = data.received_bonuses.filter(b => b.bonus_type === 'edge');

            const results = {
                type: 'edge',
                expectedCount: 0,
                actualCount: 0,
                expectedAmount: 0,
                actualAmount: receivedEdgeBonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0),
                details: []
            };

            // 엣지 보너스는 from_user_id(구매자)별로 검증
            const edgeByFromUser = {};
            receivedEdgeBonuses.forEach(b => {
                const fromUserId = b.from_user_id;
                if (!edgeByFromUser[fromUserId]) {
                    edgeByFromUser[fromUserId] = {
                        bonuses: [],
                        giver_code: b.giver_code
                    };
                }
                edgeByFromUser[fromUserId].bonuses.push(b);
            });

            // from_user_id 기준 중복 제거한 개수
            results.actualCount = Object.keys(edgeByFromUser).length;

            // 각 구매자별로 검증
            for (const fromUserId in edgeByFromUser) {
                const { bonuses, giver_code } = edgeByFromUser[fromUserId];

                try {
                    const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(fromUserId)}`);
                    const buyerData = await response.json();

                    if (buyerData.success && buyerData.sales && buyerData.sales.length > 0) {
                        const totalSales = buyerData.sales.reduce((sum, s) => sum + parseFloat(s.amount || 0), 0);
                        const expectedBonus = totalSales * 0.25;
                        const actualBonus = bonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);

                        results.expectedCount += 1;
                        results.expectedAmount += expectedBonus;

                        results.details.push({
                            user_id: fromUserId,
                            giver_code: giver_code,
                            expectedBonus: expectedBonus,
                            actualBonus: actualBonus,
                            status: Math.abs(expectedBonus - actualBonus) < 0.01 ? 'OK' : 'MISMATCH'
                        });
                    }
                } catch (error) {
                    console.error(`Error verifying ${fromUserId}:`, error);
                }
            }

            return results;
        }

        // 매칭 보너스 검증
        async function verifyMatchingBonuses(data) {
            const referrals = data.referrals || [];
            const receivedMatchingBonuses = data.received_bonuses.filter(b => b.bonus_type === 'matching');

            const results = {
                type: 'matching',
                expectedCount: 0,
                actualCount: receivedMatchingBonuses.length,
                expectedAmount: 0,
                actualAmount: receivedMatchingBonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0),
                details: []
            };

            for (const ref of referrals) {
                try {
                    const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(ref.user_id)}`);
                    const refData = await response.json();

                    if (refData.success) {
                        const edgeBonuses = refData.received_bonuses.filter(b => b.bonus_type === 'edge');
                        const uniqueEdgeFromUsers = [...new Set(edgeBonuses.map(b => b.giver_code))];
                        const edgeCount = uniqueEdgeFromUsers.length;

                        const myMatchingFromSameBuyers = receivedMatchingBonuses.filter(b =>
                            uniqueEdgeFromUsers.includes(b.giver_code)
                        );
                        const uniqueMatchingFromUsers = [...new Set(myMatchingFromSameBuyers.map(b => b.giver_code))];
                        const matchingCount = uniqueMatchingFromUsers.length;

                        results.expectedCount += edgeCount;

                        // 매칭이 누락된 구매자들 확인
                        const missingFromUsers = uniqueEdgeFromUsers.filter(
                            fromUser => !uniqueMatchingFromUsers.includes(fromUser)
                        );

                        // 매칭이 누락된 구매자들의 매칭 보너스가 다른 사람에게 발생했는지 조회
                        let missingDetails = [];
                        if (missingFromUsers.length > 0) {
                            const checkResponse = await fetch(
                                `api/member-financial.php?action=check_matching_bonuses&from_user_ids=${missingFromUsers.join(',')}`
                            );
                            const checkData = await checkResponse.json();

                            if (checkData.success) {
                                for (const fromUser of missingFromUsers) {
                                    const matchingInfo = checkData.results[fromUser];
                                    if (matchingInfo && matchingInfo.count > 0) {
                                        // 타ID 발생
                                        const receivers = matchingInfo.bonuses.map(b => b.receiver_code).join(', ');
                                        missingDetails.push({
                                            from_user: fromUser,
                                            status: 'wrong_recipient',
                                            receivers: receivers
                                        });
                                    } else {
                                        // 미발생
                                        missingDetails.push({
                                            from_user: fromUser,
                                            status: 'not_generated'
                                        });
                                    }
                                }
                            }
                        }

                        results.details.push({
                            user_id: ref.user_id,
                            name: ref.name,
                            edgeCount: edgeCount,
                            matchingCount: matchingCount,
                            missingDetails: missingDetails,
                            status: edgeCount === matchingCount ? 'OK' : 'MISMATCH'
                        });
                    }
                } catch (error) {
                    console.error(`Error verifying ${ref.user_id}:`, error);
                }
            }

            return results;
        }

        // 롤업 보너스 검증
        function verifyRollupBonuses(data) {
            const receivedRollupBonuses = data.received_bonuses.filter(b => b.bonus_type === 'rollup');

            return {
                type: 'rollup',
                actualCount: receivedRollupBonuses.length,
                actualAmount: receivedRollupBonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0),
                details: []
            };
        }

        // 결과 렌더링
        function renderResults(member, results) {
            const typeNames = {
                'referral': 'REFERRAL',
                'edge': 'EDGE',
                'matching': 'MATCHING',
                'rollup': 'ROLLUP'
            };

            let html = `
                <div class="verification-grid">
            `;

            results.forEach(result => {
                // missingBonuses 배열이 있거나, expectedCount와 actualCount가 다르면 불일치
                const hasMissingBonuses = result.missingBonuses && result.missingBonuses.length > 0;
                const hasCountMismatch = result.expectedCount !== undefined && result.expectedCount !== result.actualCount;
                const hasIssue = hasMissingBonuses || hasCountMismatch;
                const statusClass = hasIssue ? 'error' : 'ok';
                const statusText = hasIssue ? '⚠️ 불일치' : '✓ 정상';

                html += `
                    <div class="verification-card ${result.type}">
                        <div class="card-header ${result.type}">${typeNames[result.type]} 보너스</div>

                        ${result.expectedCount !== undefined ? `
                            <div class="stat-row">
                                <span class="stat-label">예상 개수</span>
                                <span class="stat-value">${result.expectedCount}개</span>
                            </div>
                        ` : ''}

                        <div class="stat-row">
                            <span class="stat-label">실제 개수</span>
                            <span class="stat-value">${result.actualCount}개</span>
                        </div>

                        ${result.expectedAmount !== undefined ? `
                            <div class="stat-row">
                                <span class="stat-label">예상 금액</span>
                                <span class="stat-value">$${result.expectedAmount.toFixed(2)}</span>
                            </div>
                        ` : ''}

                        <div class="stat-row">
                            <span class="stat-label">실제 금액</span>
                            <span class="stat-value">$${result.actualAmount.toFixed(2)}</span>
                        </div>

                        ${result.expectedAmount !== undefined ? `
                            <div class="stat-row">
                                <span class="stat-label">상태</span>
                                <span class="stat-value ${statusClass}">${statusText}</span>
                            </div>
                        ` : ''}

                        ${result.details && result.details.length > 0 ? `
                            <div class="details-section">
                                <div style="color: #94a3b8; font-size: 0.9em; margin-bottom: 10px; font-weight: 600;">상세 내역</div>
                                ${result.details.map(d => {
                                    const itemStatus = d.status === 'OK' ? 'ok' : 'error';
                                    return `
                                        <div class="detail-item ${itemStatus}">
                                            <div style="color: #f8fafc; font-weight: 600;">${d.user_id}</div>
                                            ${d.position ? `<div style="color: #94a3b8; font-size: 0.85em;">위치: ${d.position}</div>` : ''}
                                            ${d.edgeCount !== undefined ? `<div style="color: #94a3b8; font-size: 0.85em;">엣지: ${d.edgeCount} / 매칭: ${d.matchingCount}</div>` : ''}
                                            ${d.expectedBonus !== undefined ? `
                                                <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                                                    <span style="color: #94a3b8; font-size: 0.85em;">예상: $${d.expectedBonus.toFixed(2)}</span>
                                                    <span style="color: #f8fafc; font-size: 0.85em;">실제: $${d.actualBonus.toFixed(2)}</span>
                                                </div>
                                            ` : ''}
                                            ${d.missingDetails && d.missingDetails.length > 0 ? `
                                                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(148, 163, 184, 0.2);">
                                                    ${d.missingDetails.map(missing => {
                                                        if (missing.status === 'not_generated') {
                                                            return `
                                                                <div style="color: #ef4444; font-size: 0.85em; margin-top: 4px;">
                                                                    🔴 ${missing.from_user}: 미발생
                                                                </div>
                                                            `;
                                                        } else if (missing.status === 'wrong_recipient') {
                                                            return `
                                                                <div style="color: #f59e0b; font-size: 0.85em; margin-top: 4px;">
                                                                    🟠 ${missing.from_user}: 타ID 발생 → ${missing.receivers}
                                                                </div>
                                                            `;
                                                        }
                                                        return '';
                                                    }).join('')}
                                                </div>
                                            ` : ''}
                                        </div>
                                    `;
                                }).join('')}
                            </div>
                        ` : ''}
                    </div>
                `;
            });

            html += '</div>';

            document.getElementById('verificationContent').innerHTML = html;
        }
    </script>
</body>
</html>
