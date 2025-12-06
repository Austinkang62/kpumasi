# K-Pumasi 자동 배포 설정 가이드

## 📋 GitHub Secrets 설정

1. **GitHub 저장소 접속**: https://github.com/Austinkang62/kpumasi/settings/secrets/actions

2. **New repository secret** 클릭

3. 다음 5개의 Secret을 추가:

### Secret 1: FTP_SERVER
- Name: `FTP_SERVER`
- Secret: `ai22.mycafe24.com`

### Secret 2: FTP_USERNAME
- Name: `FTP_USERNAME`
- Secret: `ai22`

### Secret 3: FTP_PASSWORD
- Name: `FTP_PASSWORD`
- Secret: `Ai0505**ftd`

### Secret 4: FTP_PORT
- Name: `FTP_PORT`
- Secret: `21`

### Secret 5: FTP_PATH
- Name: `FTP_PATH`
- Secret: `www/`

---

## ✅ 설정 완료 후

이제부터 `git push`를 하면:
1. GitHub에 코드가 업로드됨
2. GitHub Actions가 자동 실행
3. Cafe24 서버에 자동으로 FTP 업로드됨

---

## 🔍 배포 상태 확인

https://github.com/Austinkang62/kpumasi/actions

---

## 🧪 테스트

Secrets 설정 완료 후, 간단한 파일을 수정하고 push하여 테스트해보세요.

```bash
git add .
git commit -m "Test auto-deploy"
git push
```
