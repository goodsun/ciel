<?php
require __DIR__ . '/../src/bootstrap.php';
$pageTitle = 'Terms of Service';
$pageHeading = 'Terms of Service';
$pageStyles = '
.lang-toggle { text-align: right; margin-bottom: 1rem; display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 0.3rem; }
.lang-toggle button { padding: 0.4rem 0.8rem; cursor: pointer; border: 1px solid #2a2a4a; background: #1a1a2e; border-radius: 4px; font-size: 0.85rem; color: #888; }
.lang-toggle button.active { background: #8bb4ff; color: #0a0a0f; border-color: #8bb4ff; }
.tos h2 { margin-top: 2.5rem; color: #8bb4ff; }
.tos h3 { margin-top: 1.5rem; color: #6690cc; }
.tos p { line-height: 1.7; }
.en, .ja, .zh, .ko, .es { display: none; }
.show-en .en { display: block; }
.show-ja .ja { display: block; }
.show-zh .zh { display: block; }
.show-ko .ko { display: block; }
.show-es .es { display: block; }
.show-all .en, .show-all .ja, .show-all .zh, .show-all .ko, .show-all .es { display: block; }
.ja, .zh, .ko, .es { color: #999; margin-top: 0.5rem; }
.show-all .ja, .show-all .zh, .show-all .ko, .show-all .es { border-left: 3px solid #2a2a4a; padding-left: 1rem; }
.tos hr { margin: 2rem 0; border: none; border-top: 1px solid #2a2a4a; }
';
require __DIR__ . '/../templates/head.php';
require __DIR__ . '/../templates/header.php';
?>

<div class="tos show-en" id="tosBody">

<div class="lang-toggle">
<button onclick="setLang('all')">All</button>
<button onclick="setLang('en')" class="active">English</button>
<button onclick="setLang('ja')">日本語</button>
<button onclick="setLang('zh')">中文</button>
<button onclick="setLang('ko')">한국어</button>
<button onclick="setLang('es')">Español</button>
</div>

<p>
<span class="en">Last updated: April 2026</span>
<span class="ja">最終更新: 2026年4月</span>
<span class="zh">最后更新：2026年4月</span>
<span class="ko">최종 업데이트: 2026년 4월</span>
<span class="es">Última actualización: abril de 2026</span>
</p>

<h2>
<span class="en">1. Service Overview</span>
<span class="ja">1. サービス概要</span>
<span class="zh">1. 服务概述</span>
<span class="ko">1. 서비스 개요</span>
<span class="es">1. Descripción del Servicio</span>
</h2>
<p class="en">CIEL is a paid image generation service powered by Stable Diffusion. This service is operated by an individual. No free tier is provided.</p>
<p class="ja">CIELはStable Diffusionを利用した有料画像生成サービスです。個人により運営されています。無料枠の提供はありません。</p>
<p class="zh">CIEL是一项基于Stable Diffusion的付费图像生成服务。本服务由个人运营，不提供免费使用额度。</p>
<p class="ko">CIEL은 Stable Diffusion 기반의 유료 이미지 생성 서비스입니다. 본 서비스는 개인이 운영하며, 무료 이용은 제공되지 않습니다.</p>
<p class="es">CIEL es un servicio de pago de generación de imágenes basado en Stable Diffusion. Este servicio es operado por un individuo. No se ofrece un nivel gratuito.</p>

<h2>
<span class="en">2. Account &amp; Authentication</span>
<span class="ja">2. アカウントと認証</span>
<span class="zh">2. 账户与认证</span>
<span class="ko">2. 계정 및 인증</span>
<span class="es">2. Cuenta y Autenticación</span>
</h2>
<p class="en">Authentication is provided exclusively via Google Account. By signing in, you agree to these terms. You are responsible for all activity under your account.</p>
<p class="ja">認証はGoogleアカウントのみで提供されます。サインインすることで本規約に同意したものとみなします。アカウント上のすべての活動はご自身の責任となります。</p>
<p class="zh">认证仅通过Google账户提供。登录即表示您同意本条款。您对账户下的所有活动承担责任。</p>
<p class="ko">인증은 Google 계정을 통해서만 제공됩니다. 로그인함으로써 본 약관에 동의하는 것으로 간주됩니다. 계정에서 발생하는 모든 활동은 본인의 책임입니다.</p>
<p class="es">La autenticación se proporciona exclusivamente a través de una cuenta de Google. Al iniciar sesión, usted acepta estos términos. Usted es responsable de toda la actividad en su cuenta.</p>

<h2>
<span class="en">3. Prepaid Credits &amp; Billing</span>
<span class="ja">3. プリペイドクレジットと課金</span>
<span class="zh">3. 预付积分与计费</span>
<span class="ko">3. 선불 크레딧 및 과금</span>
<span class="es">3. Créditos Prepagados y Facturación</span>
</h2>
<p class="en">All transactions are in USD. Users must purchase credits in advance via Stripe before using the service.</p>
<p class="ja">すべての取引はUSD建てです。サービス利用前にStripeでクレジットを事前購入する必要があります。</p>
<p class="zh">所有交易以美元结算。用户必须在使用服务前通过Stripe预先购买积分。</p>
<p class="ko">모든 거래는 USD로 이루어집니다. 서비스 이용 전 Stripe를 통해 크레딧을 사전 구매해야 합니다.</p>
<p class="es">Todas las transacciones son en USD. Los usuarios deben comprar créditos por adelantado a través de Stripe antes de usar el servicio.</p>

<h3>
<span class="en">3.1 Image Generation Fee</span>
<span class="ja">3.1 画像生成料金</span>
<span class="zh">3.1 图像生成费用</span>
<span class="ko">3.1 이미지 생성 요금</span>
<span class="es">3.1 Tarifa de Generación de Imágenes</span>
</h3>
<p class="en">Each image generation is charged based on the actual GPU cost incurred, multiplied by a margin rate. The cost is deducted from your credit balance upon completion. A per-image breakdown is shown in your transaction history.</p>
<p class="ja">画像生成1枚ごとに、実際のGPUコストにマージン率を乗じた金額が課金されます。生成完了時にクレジット残高から差し引かれます。取引履歴に1枚単位の明細が表示されます。</p>
<p class="zh">每次图像生成根据实际GPU成本乘以利润率计费。费用在生成完成时从积分余额中扣除。交易记录中显示每张图像的费用明细。</p>
<p class="ko">이미지 생성 1건마다 실제 GPU 비용에 마진율을 곱한 금액이 과금됩니다. 생성 완료 시 크레딧 잔액에서 차감됩니다. 거래 내역에서 이미지별 명세를 확인할 수 있습니다.</p>
<p class="es">Cada generación de imagen se cobra en función del costo real de GPU incurrido, multiplicado por una tasa de margen. El costo se deduce de su saldo de créditos al completarse. Se muestra un desglose por imagen en su historial de transacciones.</p>

<h3>
<span class="en">3.2 Monthly Storage Fee</span>
<span class="ja">3.2 月額ストレージ料金</span>
<span class="zh">3.2 月度存储费用</span>
<span class="ko">3.2 월별 스토리지 요금</span>
<span class="es">3.2 Tarifa Mensual de Almacenamiento</span>
</h3>
<p class="en">A monthly storage fee is charged based on your storage usage (in MB) at the end of each calendar month. The fee is deducted from your credit balance automatically.</p>
<p class="ja">毎月末時点のストレージ利用容量（MB単位）に基づき、月額ストレージ料金がクレジット残高から自動的に差し引かれます。</p>
<p class="zh">每月末根据您的存储使用量（以MB为单位）收取月度存储费用，费用将自动从积分余额中扣除。</p>
<p class="ko">매월 말 시점의 스토리지 사용량(MB 단위)에 따라 월별 스토리지 요금이 크레딧 잔액에서 자동으로 차감됩니다.</p>
<p class="es">Se cobra una tarifa mensual de almacenamiento basada en su uso de almacenamiento (en MB) al final de cada mes calendario. La tarifa se deduce automáticamente de su saldo de créditos.</p>

<h3>
<span class="en">3.3 Insufficient Balance</span>
<span class="ja">3.3 残高不足</span>
<span class="zh">3.3 余额不足</span>
<span class="ko">3.3 잔액 부족</span>
<span class="es">3.3 Saldo Insuficiente</span>
</h3>
<p class="en">A warning is displayed when your balance falls below a threshold. If your balance is insufficient at the time of generation, the image may not be generated. No refunds will be issued for failed generations due to insufficient balance.</p>
<p class="ja">残高が閾値を下回ると警告が表示されます。生成時に残高が不足している場合、画像が生成されないことがあります。残高不足による生成失敗に対する返金は行いません。</p>
<p class="zh">当余额低于阈值时将显示警告。如果生成时余额不足，图像可能无法生成。因余额不足导致的生成失败不予退款。</p>
<p class="ko">잔액이 임계값 이하로 떨어지면 경고가 표시됩니다. 생성 시 잔액이 부족한 경우 이미지가 생성되지 않을 수 있습니다. 잔액 부족으로 인한 생성 실패에 대해서는 환불하지 않습니다.</p>
<p class="es">Se muestra una advertencia cuando su saldo cae por debajo de un umbral. Si su saldo es insuficiente en el momento de la generación, la imagen puede no generarse. No se emitirán reembolsos por generaciones fallidas debido a saldo insuficiente.</p>

<h2>
<span class="en">4. No Refunds</span>
<span class="ja">4. 返金不可</span>
<span class="zh">4. 不予退款</span>
<span class="ko">4. 환불 불가</span>
<span class="es">4. Sin Reembolsos</span>
</h2>
<p class="en">All credit purchases are final. No refunds will be provided for purchased credits, failed generations, or storage charges.</p>
<p class="ja">クレジットの購入はすべて確定です。購入済みクレジット、生成失敗、ストレージ料金に対する返金は行いません。</p>
<p class="zh">所有积分购买均为最终交易。已购买的积分、生成失败或存储费用均不予退款。</p>
<p class="ko">모든 크레딧 구매는 최종 확정입니다. 구매한 크레딧, 생성 실패, 스토리지 요금에 대한 환불은 제공되지 않습니다.</p>
<p class="es">Todas las compras de créditos son definitivas. No se proporcionarán reembolsos por créditos comprados, generaciones fallidas o cargos de almacenamiento.</p>

<h2>
<span class="en">5. Generated Content</span>
<span class="ja">5. 生成コンテンツ</span>
<span class="zh">5. 生成内容</span>
<span class="ko">5. 생성 콘텐츠</span>
<span class="es">5. Contenido Generado</span>
</h2>
<p class="en">Images are generated using Stable Diffusion models. Usage rights and restrictions for generated images are subject to the applicable model license (e.g., Stability AI's policies). The operator does not claim ownership of your generated images.</p>
<p class="ja">画像はStable Diffusionモデルを使用して生成されます。生成画像の使用権および制限は、適用されるモデルライセンス（例: Stability AIのポリシー）に従います。運営者は生成画像の所有権を主張しません。</p>
<p class="zh">图像使用Stable Diffusion模型生成。生成图像的使用权和限制受适用的模型许可证（如Stability AI的政策）约束。运营者不主张对您生成的图像拥有所有权。</p>
<p class="ko">이미지는 Stable Diffusion 모델을 사용하여 생성됩니다. 생성된 이미지의 사용권 및 제한은 해당 모델 라이선스(예: Stability AI 정책)를 따릅니다. 운영자는 생성된 이미지의 소유권을 주장하지 않습니다.</p>
<p class="es">Las imágenes se generan utilizando modelos de Stable Diffusion. Los derechos de uso y las restricciones de las imágenes generadas están sujetos a la licencia del modelo aplicable (por ejemplo, las políticas de Stability AI). El operador no reclama la propiedad de sus imágenes generadas.</p>

<h2>
<span class="en">6. Prohibited Use</span>
<span class="ja">6. 禁止事項</span>
<span class="zh">6. 禁止用途</span>
<span class="ko">6. 금지 사항</span>
<span class="es">6. Uso Prohibido</span>
</h2>
<p class="en">This service is a testing platform for generative AI models. No restrictions are imposed on model capabilities. However, generating images that violate public order and morals may be subject to prosecution under the laws of the user's jurisdiction. The operator assumes no liability for such use. The operator reserves the right to suspend or terminate accounts at its discretion without prior notice.</p>
<p class="ja">本サービスは生成AIモデルのテスト用プラットフォームです。モデルの仕様に制限はかけません。ただし、公序良俗に反する画像の生成は、ユーザーが所在する国の法律に基づき処罰される可能性があります。当サイトではその責任を負いません。運営者は独自の判断により、事前通知なくアカウントを停止または終了する権利を有します。</p>
<p class="zh">本服务是生成式AI模型的测试平台。不对模型功能施加任何限制。但是，生成违反公序良俗的图像可能会依据用户所在国家的法律受到处罚。运营者不对此类使用承担任何责任。运营者保留自行决定在不事先通知的情况下暂停或终止账户的权利。</p>
<p class="ko">본 서비스는 생성 AI 모델의 테스트 플랫폼입니다. 모델 기능에 제한을 두지 않습니다. 다만, 공서양속에 반하는 이미지 생성은 이용자가 소재한 국가의 법률에 따라 처벌받을 수 있습니다. 당 사이트는 이에 대한 책임을 지지 않습니다. 운영자는 자체 판단에 따라 사전 통지 없이 계정을 정지 또는 해지할 권리를 보유합니다.</p>
<p class="es">Este servicio es una plataforma de pruebas para modelos de IA generativa. No se imponen restricciones a las capacidades del modelo. Sin embargo, la generación de imágenes que violen el orden público y la moral puede estar sujeta a procesamiento bajo las leyes de la jurisdicción del usuario. El operador no asume ninguna responsabilidad por dicho uso. El operador se reserva el derecho de suspender o cancelar cuentas a su discreción sin previo aviso.</p>

<h2>
<span class="en">7. Data Storage &amp; Deletion</span>
<span class="ja">7. データ保存と削除</span>
<span class="zh">7. 数据存储与删除</span>
<span class="ko">7. 데이터 저장 및 삭제</span>
<span class="es">7. Almacenamiento y Eliminación de Datos</span>
</h2>
<p class="en">Generated images and uploaded files are stored on the server. You may delete your files at any time. The operator may delete files of inactive accounts after reasonable notice.</p>
<p class="ja">生成画像およびアップロードファイルはサーバーに保存されます。ファイルはいつでも削除できます。運営者は合理的な通知の後、非アクティブアカウントのファイルを削除する場合があります。</p>
<p class="zh">生成的图像和上传的文件存储在服务器上。您可以随时删除您的文件。运营者可能在合理通知后删除不活跃账户的文件。</p>
<p class="ko">생성된 이미지와 업로드된 파일은 서버에 저장됩니다. 파일은 언제든지 삭제할 수 있습니다. 운영자는 합리적인 통지 후 비활성 계정의 파일을 삭제할 수 있습니다.</p>
<p class="es">Las imágenes generadas y los archivos subidos se almacenan en el servidor. Puede eliminar sus archivos en cualquier momento. El operador puede eliminar archivos de cuentas inactivas después de un aviso razonable.</p>

<h2>
<span class="en">8. Service Availability</span>
<span class="ja">8. サービスの可用性</span>
<span class="zh">8. 服务可用性</span>
<span class="ko">8. 서비스 가용성</span>
<span class="es">8. Disponibilidad del Servicio</span>
</h2>
<p class="en">This service is provided on an "as is" basis. The operator does not guarantee uptime, availability, or uninterrupted access. The service may be modified, suspended, or discontinued at any time without prior notice.</p>
<p class="ja">本サービスは「現状のまま」で提供されます。運営者は稼働時間、可用性、中断のないアクセスを保証しません。サービスは事前通知なく変更、停止、または終了される場合があります。</p>
<p class="zh">本服务按"原样"提供。运营者不保证正常运行时间、可用性或不间断访问。服务可能随时被修改、暂停或终止，恕不另行通知。</p>
<p class="ko">본 서비스는 "있는 그대로" 제공됩니다. 운영자는 가동 시간, 가용성 또는 중단 없는 접속을 보장하지 않습니다. 서비스는 사전 통지 없이 변경, 중단 또는 종료될 수 있습니다.</p>
<p class="es">Este servicio se proporciona "tal cual". El operador no garantiza el tiempo de actividad, la disponibilidad ni el acceso ininterrumpido. El servicio puede ser modificado, suspendido o descontinuado en cualquier momento sin previo aviso.</p>

<h2>
<span class="en">9. Limitation of Liability</span>
<span class="ja">9. 責任の制限</span>
<span class="zh">9. 责任限制</span>
<span class="ko">9. 책임의 제한</span>
<span class="es">9. Limitación de Responsabilidad</span>
</h2>
<p class="en">The operator shall not be liable for any direct, indirect, incidental, or consequential damages arising from the use of this service. Support is limited due to individual operation.</p>
<p class="ja">運営者は本サービスの利用から生じる直接的、間接的、偶発的、または結果的な損害について責任を負いません。個人運営のため、サポート対応範囲は限定的です。</p>
<p class="zh">运营者不对因使用本服务而产生的任何直接、间接、附带或后果性损害承担责任。由于个人运营，支持范围有限。</p>
<p class="ko">운영자는 본 서비스 이용으로 인해 발생하는 직접적, 간접적, 부수적 또는 결과적 손해에 대해 책임을 지지 않습니다. 개인 운영으로 인해 지원 범위는 제한적입니다.</p>
<p class="es">El operador no será responsable de ningún daño directo, indirecto, incidental o consecuente que surja del uso de este servicio. El soporte es limitado debido a la operación individual.</p>

<h2>
<span class="en">10. Age Requirement</span>
<span class="ja">10. 年齢要件</span>
<span class="zh">10. 年龄要求</span>
<span class="ko">10. 연령 요건</span>
<span class="es">10. Requisito de Edad</span>
</h2>
<p class="en">You must be at least 18 years of age to use this service. By using this service, you represent and warrant that you meet this age requirement. The operator is not responsible for verifying the age of users.</p>
<p class="ja">本サービスの利用には18歳以上であることが必要です。本サービスを利用することにより、この年齢要件を満たしていることを表明し保証するものとします。運営者はユーザーの年齢確認について責任を負いません。</p>
<p class="zh">您必须年满18周岁才能使用本服务。使用本服务即表示您声明并保证您符合此年龄要求。运营者不负责验证用户年龄。</p>
<p class="ko">본 서비스를 이용하려면 만 18세 이상이어야 합니다. 본 서비스를 이용함으로써 이 연령 요건을 충족함을 표명하고 보증하는 것으로 간주됩니다. 운영자는 이용자의 연령 확인에 대해 책임을 지지 않습니다.</p>
<p class="es">Debe tener al menos 18 años de edad para usar este servicio. Al usar este servicio, usted declara y garantiza que cumple con este requisito de edad. El operador no es responsable de verificar la edad de los usuarios.</p>

<h2>
<span class="en">11. Privacy &amp; Data Collection</span>
<span class="ja">11. プライバシーとデータ収集</span>
<span class="zh">11. 隐私与数据收集</span>
<span class="ko">11. 개인정보 및 데이터 수집</span>
<span class="es">11. Privacidad y Recopilación de Datos</span>
</h2>
<p class="en">This service collects the following data: Google account information (email, display name), transaction history, generation parameters and prompts, and generated images. This data is used solely for service operation, billing, and abuse prevention. Data is not sold to third parties. Data may be disclosed when required by law. Users may request deletion of their account and associated data by contacting the operator. Upon account deletion, all stored files and personal data will be removed within a reasonable period.</p>
<p class="ja">本サービスは以下のデータを収集します：Googleアカウント情報（メール、表示名）、取引履歴、生成パラメータおよびプロンプト、生成画像。これらのデータはサービス運営、課金、不正防止の目的でのみ使用されます。データは第三者に販売しません。法律で求められる場合、データを開示することがあります。ユーザーは運営者に連絡することで、アカウントおよび関連データの削除を要求できます。アカウント削除時、保存されたファイルおよび個人データは合理的な期間内に削除されます。</p>
<p class="zh">本服务收集以下数据：Google账户信息（电子邮件、显示名称）、交易记录、生成参数和提示词以及生成的图像。这些数据仅用于服务运营、计费和防止滥用。数据不会出售给第三方。法律要求时可能会披露数据。用户可以通过联系运营者请求删除其账户和相关数据。账户删除后，存储的文件和个人数据将在合理期限内删除。</p>
<p class="ko">본 서비스는 다음 데이터를 수집합니다: Google 계정 정보(이메일, 표시 이름), 거래 내역, 생성 파라미터 및 프롬프트, 생성된 이미지. 이 데이터는 서비스 운영, 과금, 부정 행위 방지 목적으로만 사용됩니다. 데이터는 제3자에게 판매하지 않습니다. 법률에 의해 요구되는 경우 데이터가 공개될 수 있습니다. 이용자는 운영자에게 연락하여 계정 및 관련 데이터의 삭제를 요청할 수 있습니다. 계정 삭제 시 저장된 파일 및 개인 데이터는 합리적인 기간 내에 삭제됩니다.</p>
<p class="es">Este servicio recopila los siguientes datos: información de la cuenta de Google (correo electrónico, nombre para mostrar), historial de transacciones, parámetros de generación y prompts, e imágenes generadas. Estos datos se utilizan únicamente para la operación del servicio, facturación y prevención de abuso. Los datos no se venden a terceros. Los datos pueden divulgarse cuando lo exija la ley. Los usuarios pueden solicitar la eliminación de su cuenta y datos asociados contactando al operador. Al eliminar la cuenta, todos los archivos almacenados y datos personales se eliminarán en un plazo razonable.</p>

<h2>
<span class="en">12. Third-Party Services</span>
<span class="ja">12. 第三者サービス</span>
<span class="zh">12. 第三方服务</span>
<span class="ko">12. 제3자 서비스</span>
<span class="es">12. Servicios de Terceros</span>
</h2>
<p class="en">This service relies on third-party services including Google (authentication), Stripe (payment processing), and RunPod (GPU infrastructure). The operator is not responsible for outages, errors, policy changes, or data handling by these third-party services. Use of these services is subject to their respective terms of service and privacy policies.</p>
<p class="ja">本サービスはGoogle（認証）、Stripe（決済処理）、RunPod（GPUインフラ）を含む第三者サービスに依存しています。運営者はこれらの第三者サービスの障害、エラー、ポリシー変更、データ取り扱いについて責任を負いません。これらのサービスの利用はそれぞれの利用規約およびプライバシーポリシーに従います。</p>
<p class="zh">本服务依赖于第三方服务，包括Google（认证）、Stripe（支付处理）和RunPod（GPU基础设施）。运营者不对这些第三方服务的中断、错误、政策变更或数据处理承担责任。使用这些服务须遵守其各自的服务条款和隐私政策。</p>
<p class="ko">본 서비스는 Google(인증), Stripe(결제 처리), RunPod(GPU 인프라)를 포함한 제3자 서비스에 의존합니다. 운영자는 이러한 제3자 서비스의 장애, 오류, 정책 변경 또는 데이터 처리에 대해 책임을 지지 않습니다. 이러한 서비스의 이용은 각각의 이용약관 및 개인정보 처리방침을 따릅니다.</p>
<p class="es">Este servicio depende de servicios de terceros, incluyendo Google (autenticación), Stripe (procesamiento de pagos) y RunPod (infraestructura GPU). El operador no es responsable de interrupciones, errores, cambios de política o manejo de datos por parte de estos servicios de terceros. El uso de estos servicios está sujeto a sus respectivos términos de servicio y políticas de privacidad.</p>

<h2>
<span class="en">13. Account Termination &amp; Remaining Balance</span>
<span class="ja">13. アカウント終了と残高</span>
<span class="zh">13. 账户终止与余额</span>
<span class="ko">13. 계정 해지 및 잔액</span>
<span class="es">13. Terminación de Cuenta y Saldo Restante</span>
</h2>
<p class="en">Upon account termination — whether initiated by the user or by the operator — any remaining credit balance is forfeited and non-refundable. Stored files will be deleted after account termination.</p>
<p class="ja">ユーザーまたは運営者によるアカウント終了時、残存するクレジット残高は放棄され、返金されません。保存されたファイルはアカウント終了後に削除されます。</p>
<p class="zh">账户终止时——无论是由用户还是运营者发起——任何剩余积分余额将被没收且不予退款。存储的文件将在账户终止后删除。</p>
<p class="ko">이용자 또는 운영자에 의한 계정 해지 시, 잔여 크레딧 잔액은 포기되며 환불되지 않습니다. 저장된 파일은 계정 해지 후 삭제됩니다.</p>
<p class="es">Al terminar la cuenta — ya sea iniciada por el usuario o por el operador — cualquier saldo de crédito restante se pierde y no es reembolsable. Los archivos almacenados se eliminarán después de la terminación de la cuenta.</p>

<h2>
<span class="en">14. Indemnification</span>
<span class="ja">14. 補償</span>
<span class="zh">14. 赔偿</span>
<span class="ko">14. 면책 보상</span>
<span class="es">14. Indemnización</span>
</h2>
<p class="en">You agree to indemnify and hold harmless the operator from any claims, damages, losses, or expenses (including legal fees) arising from your use of the service, your generated content, or your violation of these terms.</p>
<p class="ja">ユーザーは、本サービスの利用、生成したコンテンツ、または本規約への違反に起因するあらゆる請求、損害、損失、費用（弁護士費用を含む）について、運営者を補償し免責することに同意するものとします。</p>
<p class="zh">您同意就因您使用本服务、您生成的内容或您违反本条款而产生的任何索赔、损害、损失或费用（包括律师费）对运营者进行赔偿并使其免受损害。</p>
<p class="ko">이용자는 본 서비스의 이용, 생성한 콘텐츠 또는 본 약관 위반으로 인해 발생하는 모든 청구, 손해, 손실, 비용(변호사 비용 포함)에 대해 운영자를 면책하고 보상하는 데 동의합니다.</p>
<p class="es">Usted acepta indemnizar y eximir de responsabilidad al operador de cualquier reclamación, daño, pérdida o gasto (incluidos los honorarios legales) que surjan de su uso del servicio, su contenido generado o su violación de estos términos.</p>

<h2>
<span class="en">15. Governing Law &amp; Jurisdiction</span>
<span class="ja">15. 準拠法と管轄裁判所</span>
<span class="zh">15. 适用法律与管辖权</span>
<span class="ko">15. 준거법 및 관할</span>
<span class="es">15. Ley Aplicable y Jurisdicción</span>
</h2>
<p class="en">These terms shall be governed by and construed in accordance with the laws of Japan. Any disputes arising from or relating to these terms or the service shall be subject to the exclusive jurisdiction of the Tokyo District Court as the court of first instance.</p>
<p class="ja">本規約は日本法に準拠し、日本法に従って解釈されるものとします。本規約またはサービスに起因するまたは関連するすべての紛争については、東京地方裁判所を第一審の専属的合意管轄裁判所とします。</p>
<p class="zh">本条款受日本法律管辖并依据日本法律解释。因本条款或服务引起的或与之相关的任何争议，以东京地方法院为第一审专属管辖法院。</p>
<p class="ko">본 약관은 일본 법률에 의해 규율되고 해석됩니다. 본 약관 또는 서비스와 관련하여 발생하는 모든 분쟁은 도쿄 지방재판소를 제1심 전속 관할 법원으로 합니다.</p>
<p class="es">Estos términos se regirán e interpretarán de acuerdo con las leyes de Japón. Cualquier disputa que surja de o esté relacionada con estos términos o el servicio estará sujeta a la jurisdicción exclusiva del Tribunal de Distrito de Tokio como tribunal de primera instancia.</p>

<h2>
<span class="en">16. Severability</span>
<span class="ja">16. 分離可能性</span>
<span class="zh">16. 可分割性</span>
<span class="ko">16. 분리 가능성</span>
<span class="es">16. Divisibilidad</span>
</h2>
<p class="en">If any provision of these terms is found to be invalid or unenforceable, the remaining provisions shall remain in full force and effect.</p>
<p class="ja">本規約のいずれかの条項が無効または執行不能と判断された場合でも、残りの条項は引き続き完全に有効であるものとします。</p>
<p class="zh">如果本条款的任何条款被认定为无效或不可执行，其余条款仍然完全有效。</p>
<p class="ko">본 약관의 어떤 조항이 무효 또는 집행 불가능한 것으로 판명되더라도 나머지 조항은 완전한 효력을 유지합니다.</p>
<p class="es">Si alguna disposición de estos términos se considera inválida o inaplicable, las disposiciones restantes permanecerán en pleno vigor y efecto.</p>

<h2>
<span class="en">17. Force Majeure</span>
<span class="ja">17. 不可抗力</span>
<span class="zh">17. 不可抗力</span>
<span class="ko">17. 불가항력</span>
<span class="es">17. Fuerza Mayor</span>
</h2>
<p class="en">The operator shall not be liable for any failure or delay in performance resulting from causes beyond reasonable control, including but not limited to natural disasters, war, terrorism, pandemics, power outages, internet disruptions, government actions, or failures of third-party services.</p>
<p class="ja">運営者は、天災、戦争、テロ、感染症の流行、停電、インターネット障害、政府の措置、第三者サービスの障害を含むがこれらに限定されない、合理的な制御を超えた原因による履行の不能または遅延について責任を負いません。</p>
<p class="zh">运营者不对因超出合理控制范围的原因导致的任何履行失败或延迟承担责任，包括但不限于自然灾害、战争、恐怖主义、流行病、停电、互联网中断、政府行为或第三方服务故障。</p>
<p class="ko">운영자는 천재지변, 전쟁, 테러, 전염병, 정전, 인터넷 장애, 정부 조치, 제3자 서비스 장애를 포함하되 이에 한정되지 않는 합리적 통제를 벗어난 원인으로 인한 이행 불능 또는 지연에 대해 책임을 지지 않습니다.</p>
<p class="es">El operador no será responsable de ningún incumplimiento o retraso en el rendimiento resultante de causas fuera de su control razonable, incluyendo pero no limitado a desastres naturales, guerra, terrorismo, pandemias, cortes de energía, interrupciones de internet, acciones gubernamentales o fallos de servicios de terceros.</p>

<h2>
<span class="en">18. Changes to Terms</span>
<span class="ja">18. 規約の変更</span>
<span class="zh">18. 条款变更</span>
<span class="ko">18. 약관 변경</span>
<span class="es">18. Cambios en los Términos</span>
</h2>
<p class="en">These terms may be updated at any time. Continued use of the service after changes constitutes acceptance of the new terms.</p>
<p class="ja">本規約は随時更新される場合があります。変更後にサービスを継続して利用することで、新しい規約に同意したものとみなされます。</p>
<p class="zh">本条款可能随时更新。变更后继续使用服务即表示接受新条款。</p>
<p class="ko">본 약관은 언제든지 업데이트될 수 있습니다. 변경 후 서비스를 계속 이용하면 새로운 약관에 동의한 것으로 간주됩니다.</p>
<p class="es">Estos términos pueden actualizarse en cualquier momento. El uso continuado del servicio después de los cambios constituye la aceptación de los nuevos términos.</p>

<hr>

<p>
<span class="en">Contact: See the service website for operator contact information.</span>
<span class="ja">連絡先: 運営者の連絡先はサービスサイトをご参照ください。</span>
<span class="zh">联系方式：请参阅服务网站获取运营者联系信息。</span>
<span class="ko">연락처: 운영자 연락처는 서비스 웹사이트를 참조하십시오.</span>
<span class="es">Contacto: Consulte el sitio web del servicio para obtener la información de contacto del operador.</span>
</p>

</div>

<script>
function setLang(lang) {
  var el = document.getElementById('tosBody');
  el.className = 'tos show-' + lang;
  document.querySelectorAll('.lang-toggle button').forEach(function(b) {
    b.classList.remove('active');
  });
  event.target.classList.add('active');
}
</script>

<?php require __DIR__ . '/../templates/footer.php'; ?>
