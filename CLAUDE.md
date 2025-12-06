## 개발 환경 및 배포

### 호스팅 환경
- **호스팅**: 카페24 (Cafe24) 호스팅 서비스
- **서버**: PHP + MariaDB
- **문자 인코딩**: UTF-8 (모든 파일 및 데이터베이스)

### 파일 전송
- **FTP 클라이언트**: FileZilla
- 수정된 파일은 FileZilla를 통해 카페24 서버로 업로드

### 데이터베이스 관리
- **SQL 클라이언트**: HeidiSQL
- **인코딩**: UTF-8 (테이블 및 컬럼)
- MariaDB 직접 쿼리 및 스키마 관리

### 작업 규칙
**중요**: 매 작업 완료 시 반드시 수정된 파일 목록을 명시적으로 알려줄 것
```
예시:
✅ 수정된 파일:
- /mnt/c/app/AiBB/www/signup.html
- /mnt/c/app/AiBB/www/api/register.php
```