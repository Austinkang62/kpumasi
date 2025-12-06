<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>보너스 검증 리스트 - K-Pumasi Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: #0f172a;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
            border: 1px solid #3b82f6;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: rgba(59, 130, 246, 0.3);
            transform: translateX(-5px);
        }

        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .header h1 {
            color: #f8fafc;
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .info-box {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-box-text {
            color: #60a5fa;
            font-size: 0.9em;
        }

        .content-box {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            min-height: 400px;
        }

        .start-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1.1em;
            transition: all 0.3s;
        }

        .start-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .progress-box {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .progress-bar-container {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            height: 20px;
            overflow: hidden;
        }

        .progress-bar {
            background: linear-gradient(90deg, #10b981, #059669);
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }

        .results-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .member-card {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 20px;
        }

        .member-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .member-id {
            color: #60a5fa;
            font-size: 1.2em;
            font-weight: 700;
            cursor: pointer;
            text-decoration: underline;
        }

        .member-id:hover {
            color: #93c5fd;
        }

        .error-badge {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 700;
        }

        .bonus-issues-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .issue-card {
            border-radius: 8px;
            padding: 12px;
        }

        .issue-card.referral {
            background: rgba(59, 130, 246, 0.05);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .issue-card.edge {
            background: rgba(16, 185, 129, 0.05);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .issue-card.matching {
            background: rgba(251, 191, 36, 0.05);
            border: 1px solid rgba(251, 191, 36, 0.2);
        }

        .success-box {
            text-align: center;
            padding: 60px;
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid rgba(16, 185, 129, 0.3);
            border-radius: 12px;
        }

        .detail-btn {
            padding: 8px 16px;
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid #60a5fa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85em;
            margin-top: 15px;
        }

        .detail-btn:hover {
            background: rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <div class="container">
        <a href="javascript:history.back()" class="back-button">← 돌아가기</a>

        <div class="header">
            <h1>📋 전체 회원 보너스 검증 리스트</h1>
        </div>

        <div class="info-box">
            <div class="info-box-text">
                ℹ️ 전체 회원의 보너스를 검증하여 오류가 있는 회원만 표시합니다. 검증에는 시간이 소요될 수 있습니다.
            </div>
        </div>

        <div class="content-box">
            <div id="verificationContent" style="text-align: center; padding: 40px; color: #94a3b8;">
                <div style="font-size: 1.2em; margin-bottom: 20px;">전체 회원의 보너스를 검증합니다</div>
                <button onclick="runAllMembersVerification()" class="start-btn">
                    🚀 검증 시작
                </button>
            </div>
        </div>
    </div>

    <script>
        // 회원 데이터 가져오기
        async function fetchMemberData(userId) {
            try {
                const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(userId)}`);
                const data = await response.json();
                return data.success ? data : null;
            } catch (error) {
                console.error(`Error fetching data for ${userId}:`, error);
                return null;
            }
        }

        // 레퍼럴 보너스 검증
        async function verifyReferralBonuses(data) {
            const m = data.member;
            const referrals = data.referrals || [];
            const receivedReferralBonuses = data.received_bonuses.filter(b => b.bonus_type === 'referral');

            const results = {
                type: 'referral',
                missingBonuses: []
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

                        if (Math.abs(expectedBonus - actualBonus) > 0.01) {
                            const givenBonuses = refData.given_bonuses.filter(b =>
                                b.bonus_type === 'referral' &&
                                b.from_user_id === refData.member.id
                            );

                            let status = 'MISSING_NOT_GENERATED';
                            let wrongReceiver = null;

                            if (givenBonuses.length > 0) {
                                const givenToOthers = givenBonuses.filter(b => b.receiver_code !== m.user_id);
                                if (givenToOthers.length > 0) {
                                    status = 'MISSING_WRONG_RECEIVER';
                                    wrongReceiver = givenToOthers.map(b => b.receiver_code).join(', ');
                                }
                            }

                            results.missingBonuses.push({
                                user_id: ref.user_id,
                                expected: expectedBonus,
                                actual: actualBonus,
                                diff: expectedBonus - actualBonus,
                                status: status,
                                wrongReceiver: wrongReceiver
                            });
                        }
                    }
                } catch (error) {
                    console.error(`Error fetching data for ${ref.user_id}:`, error);
                }
            }

            return results;
        }

        // 엣지 보너스 검증
        async function verifyEdgeBonuses(data) {
            const m = data.member;
            const receivedEdgeBonuses = data.received_bonuses.filter(b => b.bonus_type === 'edge');

            const results = {
                type: 'edge',
                missingBonuses: []
            };

            // 엣지 보너스는 giver_code(스폰서)별로 from_user_id(구매자)에서 발생
            // 각 구매자의 매출과 실제 받은 보너스를 비교
            const edgeByFromUser = {};
            receivedEdgeBonuses.forEach(b => {
                const fromUserId = b.from_user_id;
                if (!edgeByFromUser[fromUserId]) {
                    edgeByFromUser[fromUserId] = [];
                }
                edgeByFromUser[fromUserId].push(b);
            });

            // 각 구매자별로 검증
            for (const fromUserId in edgeByFromUser) {
                const bonuses = edgeByFromUser[fromUserId];

                // 이 구매자의 매출 조회
                try {
                    const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(fromUserId)}`);
                    const buyerData = await response.json();

                    if (buyerData.success && buyerData.sales && buyerData.sales.length > 0) {
                        // 구매자의 총 매출 금액
                        const totalSales = buyerData.sales.reduce((sum, s) => sum + parseFloat(s.amount || 0), 0);
                        const expectedBonusPerEdge = totalSales * 0.25; // 25%

                        // 실제 받은 엣지 보너스 (cash + avatar_point 합계)
                        const actualBonus = bonuses.reduce((sum, b) => sum + parseFloat(b.amount || 0), 0);

                        // 금액 비교 (오차 0.01 이내)
                        if (Math.abs(expectedBonusPerEdge - actualBonus) > 0.01) {
                            results.missingBonuses.push({
                                user_id: fromUserId,
                                giver_code: bonuses[0].giver_code,
                                expected: expectedBonusPerEdge,
                                actual: actualBonus,
                                diff: expectedBonusPerEdge - actualBonus,
                                status: 'MISMATCH'
                            });
                        }
                    }
                } catch (error) {
                    console.error(`Error fetching data for ${fromUserId}:`, error);
                }
            }

            return results;
        }

        // 매칭 보너스 검증
        async function verifyMatchingBonuses(data) {
            const m = data.member;
            const referrals = data.referrals || [];
            const receivedMatchingBonuses = data.received_bonuses.filter(b => b.bonus_type === 'matching');

            const results = {
                type: 'matching',
                missingBonuses: []
            };

            for (const ref of referrals) {
                try {
                    // 추천인이 받은 엣지 보너스 조회
                    const response = await fetch(`api/member-financial.php?action=detail&user_id=${encodeURIComponent(ref.user_id)}`);
                    const refData = await response.json();

                    if (refData.success) {
                        // 추천인이 받은 엣지 보너스 (from_user_id 기준 중복 제거)
                        const edgeBonuses = refData.received_bonuses.filter(b => b.bonus_type === 'edge');
                        const uniqueEdgeFromUsers = [...new Set(edgeBonuses.map(b => b.from_user_id))];
                        const expectedCount = uniqueEdgeFromUsers.length;

                        // 내가 받은 매칭 보너스 중 추천인이 엣지를 받은 구매자와 동일한 from_user_id
                        const myMatchingFromSameBuyers = receivedMatchingBonuses.filter(b =>
                            uniqueEdgeFromUsers.includes(b.from_user_id)
                        );
                        const uniqueMatchingFromUsers = [...new Set(myMatchingFromSameBuyers.map(b => b.from_user_id))];
                        const actualCount = uniqueMatchingFromUsers.length;

                        if (expectedCount !== actualCount) {
                            results.missingBonuses.push({
                                user_id: ref.user_id,
                                expectedCount: expectedCount,
                                actualCount: actualCount,
                                expected: expectedCount,
                                actual: actualCount,
                                diff: expectedCount - actualCount,
                                status: 'MISMATCH'
                            });
                        }
                    }
                } catch (error) {
                    console.error(`Error fetching data for ${ref.user_id}:`, error);
                }
            }

            return results;
        }

        // 전체 회원 검증 실행
        async function runAllMembersVerification() {
            const content = document.getElementById('verificationContent');
            content.innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <div style="color: #60a5fa; font-size: 1.2em; margin-bottom: 20px;">🔍 회원 목록을 가져오는 중...</div>
                </div>
            `;

            try {
                const response = await fetch('api/member-financial.php?action=list&limit=1000');
                const data = await response.json();

                if (!data.success || !data.members) {
                    throw new Error('회원 목록을 가져올 수 없습니다.');
                }

                const members = data.members;
                const totalMembers = members.length;

                content.innerHTML = `
                    <div class="progress-box">
                        <div style="color: #f8fafc; font-size: 1.1em; margin-bottom: 10px;">
                            전체 회원: <span style="color: #60a5fa; font-weight: 700;">${totalMembers}명</span>
                        </div>
                        <div style="color: #94a3b8; font-size: 0.9em; margin-bottom: 15px;">
                            진행 상황: <span id="progressCount" style="color: #10b981; font-weight: 600;">0</span> / ${totalMembers}
                        </div>
                        <div class="progress-bar-container">
                            <div id="progressBar" class="progress-bar"></div>
                        </div>
                    </div>

                    <div id="verificationResults" class="results-grid">
                        <div style="text-align: center; color: #94a3b8; padding: 20px;">검증 중...</div>
                    </div>
                `;

                const membersWithIssues = [];
                let checkedCount = 0;

                const batchSize = 5;
                for (let i = 0; i < members.length; i += batchSize) {
                    const batch = members.slice(i, i + batchSize);

                    const batchPromises = batch.map(async (member) => {
                        try {
                            const memberData = await fetchMemberData(member.user_id);
                            if (!memberData) return null;

                            const referralResults = await verifyReferralBonuses(memberData);
                            const edgeResults = await verifyEdgeBonuses(memberData);
                            const matchingResults = await verifyMatchingBonuses(memberData);

                            const hasIssues =
                                referralResults.missingBonuses.length > 0 ||
                                edgeResults.missingBonuses.length > 0 ||
                                matchingResults.missingBonuses.length > 0;

                            if (hasIssues) {
                                return {
                                    member: member,
                                    referralIssues: referralResults.missingBonuses,
                                    edgeIssues: edgeResults.missingBonuses,
                                    matchingIssues: matchingResults.missingBonuses,
                                    totalIssues: referralResults.missingBonuses.length +
                                                edgeResults.missingBonuses.length +
                                                matchingResults.missingBonuses.length
                                };
                            }
                            return null;
                        } catch (error) {
                            console.error(`Error verifying ${member.user_id}:`, error);
                            return null;
                        }
                    });

                    const batchResults = await Promise.all(batchPromises);
                    batchResults.forEach(result => {
                        if (result) membersWithIssues.push(result);
                    });

                    checkedCount += batch.length;
                    const progressPercent = Math.round((checkedCount / totalMembers) * 100);
                    document.getElementById('progressCount').textContent = checkedCount;
                    document.getElementById('progressBar').style.width = progressPercent + '%';
                }

                renderVerificationList(membersWithIssues, totalMembers);

            } catch (error) {
                console.error('검증 중 오류:', error);
                content.innerHTML = `
                    <div style="color: #ef4444; padding: 20px; text-align: center;">
                        ❌ 오류가 발생했습니다: ${error.message}
                    </div>
                `;
            }
        }

        // 검증 리스트 렌더링
        function renderVerificationList(membersWithIssues, totalMembers) {
            const resultsDiv = document.getElementById('verificationResults');

            if (membersWithIssues.length === 0) {
                resultsDiv.innerHTML = `
                    <div class="success-box">
                        <div style="color: #10b981; font-size: 2em; margin-bottom: 15px;">✓</div>
                        <div style="color: #10b981; font-size: 1.3em; font-weight: 700; margin-bottom: 10px;">모든 회원의 보너스가 정상입니다!</div>
                        <div style="color: #94a3b8; font-size: 0.9em;">총 ${totalMembers}명 검증 완료</div>
                    </div>
                `;
                return;
            }

            membersWithIssues.sort((a, b) => b.totalIssues - a.totalIssues);

            // 보너스별 총 건수 계산
            let totalReferralIssues = 0;
            let totalEdgeIssues = 0;
            let totalMatchingIssues = 0;

            membersWithIssues.forEach(item => {
                totalReferralIssues += item.referralIssues.length;
                totalEdgeIssues += item.edgeIssues.length;
                totalMatchingIssues += item.matchingIssues.length;
            });

            const statusLabels = {
                'MISSING_NOT_GENERATED': '<span style="color: #94a3b8;">미발생</span>',
                'MISSING_WRONG_RECEIVER': '<span style="color: #fbbf24;">다른ID 발생</span>'
            };

            let html = `
                <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <div style="color: #ef4444; font-size: 1.1em; font-weight: 700; margin-bottom: 15px;">
                        ⚠️ 오류 발견: <span style="color: #fca5a5;">${membersWithIssues.length}명</span> / ${totalMembers}명
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
                        <div style="background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 12px; text-align: center;">
                            <div style="color: #60a5fa; font-size: 0.85em; margin-bottom: 5px;">REFERRAL</div>
                            <div style="color: #ef4444; font-size: 1.5em; font-weight: 700;">${totalReferralIssues}건</div>
                        </div>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 12px; text-align: center;">
                            <div style="color: #10b981; font-size: 0.85em; margin-bottom: 5px;">EDGE</div>
                            <div style="color: #ef4444; font-size: 1.5em; font-weight: 700;">${totalEdgeIssues}건</div>
                        </div>
                        <div style="background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 8px; padding: 12px; text-align: center;">
                            <div style="color: #fbbf24; font-size: 0.85em; margin-bottom: 5px;">MATCHING</div>
                            <div style="color: #ef4444; font-size: 1.5em; font-weight: 700;">${totalMatchingIssues}건</div>
                        </div>
                    </div>
                </div>
            `;

            membersWithIssues.forEach((item) => {
                const m = item.member;

                html += `
                    <div class="member-card">
                        <div class="member-card-header">
                            <div>
                                <span class="member-id" onclick="window.open('user-bonus-received.php?user_id=${m.user_id}', '_blank')">${m.user_id}</span>
                                <span style="color: #94a3b8; margin-left: 10px;">${m.name || '-'}</span>
                            </div>
                            <div class="error-badge">
                                ${item.totalIssues}건 오류
                            </div>
                        </div>

                        <div class="bonus-issues-grid">
                            ${item.referralIssues.length > 0 ? `
                                <div class="issue-card referral">
                                    <div style="color: #60a5fa; font-weight: 600; margin-bottom: 8px;">REFERRAL (${item.referralIssues.length}건)</div>
                                    ${item.referralIssues.slice(0, 3).map(issue => `
                                        <div style="color: #fca5a5; font-size: 0.85em; padding: 4px 0; border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                                            ${issue.user_id}: $${issue.diff.toFixed(2)} ${statusLabels[issue.status] || ''}
                                            ${issue.wrongReceiver ? `<br><span style="color: #fbbf24; font-size: 0.8em;">→ ${issue.wrongReceiver}</span>` : ''}
                                        </div>
                                    `).join('')}
                                    ${item.referralIssues.length > 3 ? `<div style="color: #64748b; font-size: 0.8em; margin-top: 5px;">... 외 ${item.referralIssues.length - 3}건</div>` : ''}
                                </div>
                            ` : ''}

                            ${item.edgeIssues.length > 0 ? `
                                <div class="issue-card edge">
                                    <div style="color: #10b981; font-weight: 600; margin-bottom: 8px;">EDGE (${item.edgeIssues.length}건)</div>
                                    ${item.edgeIssues.slice(0, 3).map(issue => `
                                        <div style="color: #fca5a5; font-size: 0.85em; padding: 4px 0; border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                                            ${issue.user_id}: $${issue.diff.toFixed(2)} ${statusLabels[issue.status] || ''}
                                            ${issue.wrongReceiver ? `<br><span style="color: #fbbf24; font-size: 0.8em;">→ ${issue.wrongReceiver}</span>` : ''}
                                        </div>
                                    `).join('')}
                                    ${item.edgeIssues.length > 3 ? `<div style="color: #64748b; font-size: 0.8em; margin-top: 5px;">... 외 ${item.edgeIssues.length - 3}건</div>` : ''}
                                </div>
                            ` : ''}

                            ${item.matchingIssues.length > 0 ? `
                                <div class="issue-card matching">
                                    <div style="color: #fbbf24; font-weight: 600; margin-bottom: 8px;">MATCHING (${item.matchingIssues.length}건)</div>
                                    ${item.matchingIssues.slice(0, 3).map(issue => `
                                        <div style="color: #fca5a5; font-size: 0.85em; padding: 4px 0; border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                                            ${issue.user_id}: $${issue.diff.toFixed(2)} ${statusLabels[issue.status] || ''}
                                            ${issue.wrongReceiver ? `<br><span style="color: #fbbf24; font-size: 0.8em;">→ ${issue.wrongReceiver}</span>` : ''}
                                        </div>
                                    `).join('')}
                                    ${item.matchingIssues.length > 3 ? `<div style="color: #64748b; font-size: 0.8em; margin-top: 5px;">... 외 ${item.matchingIssues.length - 3}건</div>` : ''}
                                </div>
                            ` : ''}
                        </div>

                        <button onclick="window.open('user-bonus-received.php?user_id=${m.user_id}', '_blank')" class="detail-btn">
                            상세 보기
                        </button>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
        }
    </script>
</body>
</html>
