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
<span class="en">3.2 Cost Calculation Method</span>
<span class="ja">3.2 コスト算出方法</span>
<span class="zh">3.2 费用计算方法</span>
<span class="ko">3.2 비용 산출 방법</span>
<span class="es">3.2 Método de Cálculo de Costos</span>
</h3>
<p class="en">Generation costs are calculated based on actual GPU infrastructure billing data, not a fixed per-image rate. The infrastructure provider bills GPU usage in hourly aggregated buckets, and the cost of each bucket is proportionally distributed across all jobs processed within that period based on their execution time. This means that shared infrastructure costs — including GPU startup (cold start) time and idle time between jobs — are distributed among all users who utilized the same GPU worker during that period. The final cost charged to you is this proportional share multiplied by a margin rate. Costs may be finalized after generation is complete, as billing data becomes available with a delay.</p>
<p class="ja">生成コストは固定の1枚単価ではなく、実際のGPUインフラ課金データに基づいて算出されます。インフラプロバイダーはGPU利用を1時間単位のバケットで課金し、各バケットのコストは処理時間に基づいて同一期間内の全ジョブに按分されます。つまり、GPUの起動コスト（コールドスタート）やジョブ間のアイドル時間など、インフラの共有コストは同一期間に同じGPUワーカーを利用した全ユーザーで按分されます。最終的なユーザー課金額は、この按分額にマージン率を乗じた金額です。課金データの取得に時間がかかるため、コストは生成完了後に確定する場合があります。</p>
<p class="zh">生成费用基于实际GPU基础设施计费数据计算，而非固定的每张图片费率。基础设施提供商按每小时汇总计费，每个时段的费用按执行时间比例分配给该时段内处理的所有任务。这意味着共享基础设施成本——包括GPU启动（冷启动）时间和任务间的空闲时间——由同一时段内使用同一GPU工作节点的所有用户分摊。最终向您收取的费用为该比例份额乘以利润率。由于计费数据存在延迟，费用可能在生成完成后才最终确定。</p>
<p class="ko">생성 비용은 고정된 이미지당 단가가 아닌 실제 GPU 인프라 과금 데이터를 기반으로 산출됩니다. 인프라 제공업체는 GPU 사용량을 시간 단위 버킷으로 과금하며, 각 버킷의 비용은 해당 기간에 처리된 모든 작업의 실행 시간에 비례하여 배분됩니다. 즉, GPU 시작(콜드 스타트) 시간과 작업 간 유휴 시간 등 공유 인프라 비용은 동일 기간에 같은 GPU 워커를 이용한 모든 사용자가 분담합니다. 최종 과금액은 이 비례 배분액에 마진율을 곱한 금액입니다. 과금 데이터 확보에 시간이 걸리므로, 비용은 생성 완료 후에 확정될 수 있습니다.</p>
<p class="es">Los costos de generación se calculan en base a los datos reales de facturación de la infraestructura GPU, no a una tarifa fija por imagen. El proveedor de infraestructura factura el uso de GPU en intervalos horarios agregados, y el costo de cada intervalo se distribuye proporcionalmente entre todos los trabajos procesados durante ese período según su tiempo de ejecución. Esto significa que los costos compartidos de infraestructura — incluido el tiempo de arranque de GPU (arranque en frío) y el tiempo de inactividad entre trabajos — se distribuyen entre todos los usuarios que utilizaron el mismo trabajador GPU durante ese período. El costo final que se le cobra es esta proporción multiplicada por una tasa de margen. Los costos pueden finalizarse después de que se complete la generación, ya que los datos de facturación se obtienen con retraso.</p>

<h3>
<span class="en">3.3 Monthly Storage Fee</span>
<span class="ja">3.3 月額ストレージ料金</span>
<span class="zh">3.3 月度存储费用</span>
<span class="ko">3.3 월별 스토리지 요금</span>
<span class="es">3.3 Tarifa Mensual de Almacenamiento</span>
</h3>
<p class="en">A monthly storage fee is charged based on your storage usage (in MB) at the end of each calendar month. The fee is deducted from your credit balance automatically.</p>
<p class="ja">毎月末時点のストレージ利用容量（MB単位）に基づき、月額ストレージ料金がクレジット残高から自動的に差し引かれます。</p>
<p class="zh">每月末根据您的存储使用量（以MB为单位）收取月度存储费用，费用将自动从积分余额中扣除。</p>
<p class="ko">매월 말 시점의 스토리지 사용량(MB 단위)에 따라 월별 스토리지 요금이 크레딧 잔액에서 자동으로 차감됩니다.</p>
<p class="es">Se cobra una tarifa mensual de almacenamiento basada en su uso de almacenamiento (en MB) al final de cada mes calendario. La tarifa se deduce automáticamente de su saldo de créditos.</p>

<h3>
<span class="en">3.4 Insufficient Balance</span>
<span class="ja">3.4 残高不足</span>
<span class="zh">3.4 余额不足</span>
<span class="ko">3.4 잔액 부족</span>
<span class="es">3.4 Saldo Insuficiente</span>
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
<span class="en">5. Generated Content &amp; User Responsibility</span>
<span class="ja">5. 生成コンテンツとユーザーの責任</span>
<span class="zh">5. 生成内容与用户责任</span>
<span class="ko">5. 생성 콘텐츠 및 사용자 책임</span>
<span class="es">5. Contenido Generado y Responsabilidad del Usuario</span>
</h2>

<h3>
<span class="en">5.1 Content Ownership</span>
<span class="ja">5.1 コンテンツの所有権</span>
<span class="zh">5.1 内容所有权</span>
<span class="ko">5.1 콘텐츠 소유권</span>
<span class="es">5.1 Propiedad del Contenido</span>
</h3>
<p class="en">Images are generated using AI models including Stable Diffusion. The operator does not claim ownership of your generated images. However, usage rights and restrictions for generated images are subject to the applicable model license (e.g., Stability AI's policies). You are solely responsible for ensuring that your use of generated images complies with applicable model licenses and all applicable laws.</p>
<p class="ja">画像はStable Diffusionを含むAIモデルを使用して生成されます。運営者は生成画像の所有権を主張しません。ただし、生成画像の使用権および制限は適用されるモデルライセンス（例: Stability AIのポリシー）に従います。生成画像の利用が適用されるモデルライセンスおよびすべての適用法令に準拠していることを確認する責任は、ユーザーのみに帰属します。</p>
<p class="zh">图像使用包括Stable Diffusion在内的AI模型生成。运营者不主张对您生成的图像拥有所有权。但生成图像的使用权和限制受适用的模型许可证（如Stability AI的政策）约束。您有责任确保您对生成图像的使用符合适用的模型许可证和所有适用法律。</p>
<p class="ko">이미지는 Stable Diffusion을 포함한 AI 모델을 사용하여 생성됩니다. 운영자는 생성된 이미지의 소유권을 주장하지 않습니다. 다만 생성된 이미지의 사용권 및 제한은 해당 모델 라이선스(예: Stability AI 정책)를 따릅니다. 생성된 이미지의 사용이 해당 모델 라이선스 및 모든 관련 법률을 준수하는지 확인할 책임은 전적으로 사용자에게 있습니다.</p>
<p class="es">Las imágenes se generan utilizando modelos de IA, incluyendo Stable Diffusion. El operador no reclama la propiedad de sus imágenes generadas. Sin embargo, los derechos de uso y las restricciones de las imágenes generadas están sujetos a la licencia del modelo aplicable (por ejemplo, las políticas de Stability AI). Usted es el único responsable de garantizar que su uso de las imágenes generadas cumpla con las licencias de modelo aplicables y todas las leyes vigentes.</p>

<h3>
<span class="en">5.2 Full User Responsibility for Generated Content</span>
<span class="ja">5.2 生成コンテンツに対するユーザーの全責任</span>
<span class="zh">5.2 用户对生成内容的全部责任</span>
<span class="ko">5.2 생성 콘텐츠에 대한 사용자 전적 책임</span>
<span class="es">5.2 Responsabilidad Total del Usuario sobre el Contenido Generado</span>
</h3>
<p class="en">You are fully and solely responsible for all content you generate, download, share, publish, or otherwise distribute using this service. This includes, but is not limited to, responsibility for any claims of intellectual property infringement, defamation, privacy violations, or any other legal claims arising from the content. The operator provides only the technical infrastructure for image generation and bears no responsibility whatsoever for the content created by users.</p>
<p class="ja">本サービスを使用して生成、ダウンロード、共有、公開、またはその他の方法で配布するすべてのコンテンツについて、ユーザーが完全かつ単独で責任を負います。これには、知的財産権侵害、名誉毀損、プライバシー侵害、またはコンテンツに起因するその他の法的請求に対する責任が含まれますが、これらに限定されません。運営者は画像生成のための技術的インフラを提供するのみであり、ユーザーが作成したコンテンツについて一切の責任を負いません。</p>
<p class="zh">您对使用本服务生成、下载、共享、发布或以其他方式分发的所有内容承担全部且唯一的责任。这包括但不限于因内容引起的任何知识产权侵权、诽谤、隐私侵犯或任何其他法律索赔的责任。运营者仅提供图像生成的技术基础设施，对用户创建的内容不承担任何责任。</p>
<p class="ko">본 서비스를 사용하여 생성, 다운로드, 공유, 게시 또는 기타 방법으로 배포하는 모든 콘텐츠에 대해 사용자가 전적으로 책임을 집니다. 여기에는 지적 재산권 침해, 명예 훼손, 개인정보 침해 또는 콘텐츠로 인한 기타 법적 청구에 대한 책임이 포함되며 이에 한정되지 않습니다. 운영자는 이미지 생성을 위한 기술 인프라만 제공하며, 사용자가 생성한 콘텐츠에 대해 어떠한 책임도 지지 않습니다.</p>
<p class="es">Usted es total y exclusivamente responsable de todo el contenido que genere, descargue, comparta, publique o distribuya de cualquier otra forma utilizando este servicio. Esto incluye, entre otros, la responsabilidad por cualquier reclamación de infracción de propiedad intelectual, difamación, violaciones de privacidad o cualquier otra reclamación legal derivada del contenido. El operador solo proporciona la infraestructura técnica para la generación de imágenes y no asume ninguna responsabilidad por el contenido creado por los usuarios.</p>

<h3>
<span class="en">5.3 No Warranty of Non-Infringement</span>
<span class="ja">5.3 非侵害の保証なし</span>
<span class="zh">5.3 不保证不侵权</span>
<span class="ko">5.3 비침해 보증 없음</span>
<span class="es">5.3 Sin Garantía de No Infracción</span>
</h3>
<p class="en">AI-generated images may unintentionally resemble existing copyrighted works, trademarks, or other protected materials. The operator makes no warranty or representation that any generated content will not infringe upon the intellectual property rights or other rights of any third party. You assume all risk associated with the use of generated content.</p>
<p class="ja">AI生成画像は、意図せず既存の著作物、商標、またはその他の保護された素材に類似する場合があります。運営者は、生成コンテンツが第三者の知的財産権またはその他の権利を侵害しないことについて、いかなる保証も表明も行いません。生成コンテンツの使用に伴うすべてのリスクはユーザーが負うものとします。</p>
<p class="zh">AI生成的图像可能无意中与现有的受版权保护的作品、商标或其他受保护材料相似。运营者不保证也不声明任何生成内容不会侵犯任何第三方的知识产权或其他权利。您承担与使用生成内容相关的所有风险。</p>
<p class="ko">AI 생성 이미지는 의도치 않게 기존 저작물, 상표 또는 기타 보호된 자료와 유사할 수 있습니다. 운영자는 생성된 콘텐츠가 제3자의 지적 재산권 또는 기타 권리를 침해하지 않는다는 어떠한 보증이나 진술도 하지 않습니다. 생성된 콘텐츠의 사용과 관련된 모든 위험은 사용자가 부담합니다.</p>
<p class="es">Las imágenes generadas por IA pueden parecerse involuntariamente a obras con derechos de autor, marcas registradas u otros materiales protegidos existentes. El operador no ofrece ninguna garantía ni declaración de que el contenido generado no infringirá los derechos de propiedad intelectual u otros derechos de terceros. Usted asume todo el riesgo asociado con el uso del contenido generado.</p>

<h3>
<span class="en">5.4 License Grant to Operator</span>
<span class="ja">5.4 運営者へのライセンス付与</span>
<span class="zh">5.4 授予运营者的许可</span>
<span class="ko">5.4 운영자에 대한 라이선스 부여</span>
<span class="es">5.4 Licencia Otorgada al Operador</span>
</h3>
<p class="en">By using this service, you grant the operator a non-exclusive, worldwide, royalty-free license to use your prompts and generation parameters solely for the purposes of service operation, improvement, and abuse prevention. The operator will not use your generated images for marketing or promotional purposes without your explicit consent.</p>
<p class="ja">本サービスを利用することにより、ユーザーは運営者に対し、サービスの運営、改善、および不正防止の目的に限り、プロンプトおよび生成パラメータを使用する非独占的、全世界的、ロイヤリティフリーのライセンスを付与するものとします。運営者は、ユーザーの明示的な同意なく、生成画像をマーケティングまたはプロモーション目的で使用することはありません。</p>
<p class="zh">使用本服务即表示您授予运营者非独占性、全球性、免版税的许可，仅用于服务运营、改进和防止滥用的目的使用您的提示词和生成参数。未经您的明确同意，运营者不会将您生成的图像用于营销或推广目的。</p>
<p class="ko">본 서비스를 이용함으로써 사용자는 운영자에게 서비스 운영, 개선 및 부정 행위 방지 목적에 한하여 프롬프트 및 생성 파라미터를 사용할 수 있는 비독점적, 전 세계적, 로열티 프리 라이선스를 부여합니다. 운영자는 사용자의 명시적 동의 없이 생성된 이미지를 마케팅 또는 홍보 목적으로 사용하지 않습니다.</p>
<p class="es">Al usar este servicio, usted otorga al operador una licencia no exclusiva, mundial y libre de regalías para usar sus prompts y parámetros de generación únicamente con fines de operación del servicio, mejora y prevención de abuso. El operador no utilizará sus imágenes generadas con fines de marketing o promoción sin su consentimiento explícito.</p>

<h2>
<span class="en">6. Prohibited Content &amp; Use</span>
<span class="ja">6. 禁止コンテンツおよび禁止行為</span>
<span class="zh">6. 禁止内容与禁止行为</span>
<span class="ko">6. 금지 콘텐츠 및 금지 행위</span>
<span class="es">6. Contenido y Uso Prohibido</span>
</h2>
<p class="en">This service is a testing platform for generative AI models. While the platform provides broad creative freedom, the following uses are strictly prohibited:</p>
<p class="ja">本サービスは生成AIモデルのテスト用プラットフォームです。幅広い創作の自由を提供しますが、以下の行為は厳格に禁止されます：</p>
<p class="zh">本服务是生成式AI模型的测试平台。虽然平台提供广泛的创作自由，但以下行为严格禁止：</p>
<p class="ko">본 서비스는 생성 AI 모델의 테스트 플랫폼입니다. 폭넓은 창작의 자유를 제공하지만, 다음 행위는 엄격히 금지됩니다:</p>
<p class="es">Este servicio es una plataforma de pruebas para modelos de IA generativa. Si bien la plataforma ofrece amplia libertad creativa, los siguientes usos están estrictamente prohibidos:</p>

<h3>
<span class="en">6.1 Copyright Infringement</span>
<span class="ja">6.1 著作権侵害</span>
<span class="zh">6.1 版权侵权</span>
<span class="ko">6.1 저작권 침해</span>
<span class="es">6.1 Infracción de Derechos de Autor</span>
</h3>
<p class="en">You must not intentionally generate images that reproduce, replicate, or substantially copy copyrighted works, including but not limited to: reproducing existing artworks, photographs, or illustrations; generating images that deliberately imitate the distinctive style of a specific living artist for commercial purposes; creating unauthorized reproductions of branded or trademarked materials. You are solely responsible for verifying that your generated content does not infringe upon the intellectual property rights of others before any use, distribution, or publication.</p>
<p class="ja">著作権で保護された著作物を再現、複製、または実質的にコピーする画像を意図的に生成してはなりません。これには以下が含まれますが、これらに限定されません：既存のアートワーク、写真、またはイラストの再現、商業目的での特定の存命アーティストの独自のスタイルを意図的に模倣した画像の生成、ブランドまたは商標素材の無断複製。使用、配布、または公開の前に、生成コンテンツが他者の知的財産権を侵害していないことを確認する責任は、ユーザーのみに帰属します。</p>
<p class="zh">您不得故意生成复制、翻制或实质性复制受版权保护作品的图像，包括但不限于：复制现有的艺术品、照片或插图；出于商业目的故意模仿特定在世艺术家的独特风格来生成图像；未经授权复制品牌或商标材料。在任何使用、分发或发布之前，您有责任验证您生成的内容不侵犯他人的知识产权。</p>
<p class="ko">저작권으로 보호된 저작물을 재현, 복제 또는 실질적으로 모방하는 이미지를 의도적으로 생성해서는 안 됩니다. 여기에는 다음이 포함되나 이에 한정되지 않습니다: 기존 예술 작품, 사진 또는 일러스트레이션의 재현, 상업적 목적으로 특정 생존 아티스트의 고유한 스타일을 의도적으로 모방한 이미지 생성, 브랜드 또는 상표 자료의 무단 복제. 사용, 배포 또는 게시 전에 생성된 콘텐츠가 타인의 지적 재산권을 침해하지 않는지 확인할 책임은 전적으로 사용자에게 있습니다.</p>
<p class="es">No debe generar intencionalmente imágenes que reproduzcan, repliquen o copien sustancialmente obras protegidas por derechos de autor, incluyendo pero no limitado a: reproducir obras de arte, fotografías o ilustraciones existentes; generar imágenes que imiten deliberadamente el estilo distintivo de un artista vivo específico con fines comerciales; crear reproducciones no autorizadas de materiales de marca o registrados. Usted es el único responsable de verificar que su contenido generado no infrinja los derechos de propiedad intelectual de terceros antes de cualquier uso, distribución o publicación.</p>

<h3>
<span class="en">6.2 Illegal and Harmful Content</span>
<span class="ja">6.2 違法および有害なコンテンツ</span>
<span class="zh">6.2 违法和有害内容</span>
<span class="ko">6.2 불법 및 유해 콘텐츠</span>
<span class="es">6.2 Contenido Ilegal y Dañino</span>
</h3>
<p class="en">The following content is strictly prohibited: (a) child sexual abuse material (CSAM) or any sexual depiction of minors; (b) non-consensual intimate imagery or deepfakes of real persons; (c) content that promotes terrorism, extreme violence, or incites hatred against protected groups; (d) content designed to harass, threaten, or intimidate specific individuals; (e) fraudulent content intended to deceive others, including for scams or misinformation campaigns; (f) any content that violates applicable laws in your jurisdiction or the jurisdiction of Japan.</p>
<p class="ja">以下のコンテンツは厳格に禁止されます：(a) 児童性的虐待素材（CSAM）または未成年者の性的描写、(b) 実在する人物の同意のない親密な画像またはディープフェイク、(c) テロリズム、過激な暴力を促進する、または保護されたグループに対する憎悪を扇動するコンテンツ、(d) 特定の個人を嫌がらせ、脅迫、または威圧するために作成されたコンテンツ、(e) 詐欺や偽情報キャンペーンを含む、他者を欺くことを目的とした不正コンテンツ、(f) ユーザーの管轄地域または日本の法律に違反するあらゆるコンテンツ。</p>
<p class="zh">以下内容严格禁止：(a) 儿童性虐待材料（CSAM）或任何对未成年人的性描写；(b) 未经同意的真实人物亲密图像或深度伪造；(c) 宣扬恐怖主义、极端暴力或煽动对受保护群体仇恨的内容；(d) 旨在骚扰、威胁或恐吓特定个人的内容；(e) 旨在欺骗他人的欺诈性内容，包括用于诈骗或虚假信息活动；(f) 违反您所在司法管辖区或日本法律的任何内容。</p>
<p class="ko">다음 콘텐츠는 엄격히 금지됩니다: (a) 아동 성적 학대 자료(CSAM) 또는 미성년자에 대한 성적 묘사, (b) 실존 인물의 비동의 친밀한 이미지 또는 딥페이크, (c) 테러리즘, 극단적 폭력을 조장하거나 보호 대상 집단에 대한 혐오를 선동하는 콘텐츠, (d) 특정 개인을 괴롭히거나 위협하거나 협박하기 위해 설계된 콘텐츠, (e) 사기 또는 허위 정보 캠페인을 포함하여 타인을 기만하기 위한 사기성 콘텐츠, (f) 이용자의 관할 지역 또는 일본 법률에 위반되는 모든 콘텐츠.</p>
<p class="es">El siguiente contenido está estrictamente prohibido: (a) material de abuso sexual infantil (CSAM) o cualquier representación sexual de menores; (b) imágenes íntimas no consentidas o deepfakes de personas reales; (c) contenido que promueva el terrorismo, la violencia extrema o incite al odio contra grupos protegidos; (d) contenido diseñado para acosar, amenazar o intimidar a personas específicas; (e) contenido fraudulento destinado a engañar a otros, incluyendo estafas o campañas de desinformación; (f) cualquier contenido que viole las leyes aplicables en su jurisdicción o la jurisdicción de Japón.</p>

<h3>
<span class="en">6.3 Content Monitoring &amp; Enforcement</span>
<span class="ja">6.3 コンテンツの監視と執行</span>
<span class="zh">6.3 内容监控与执行</span>
<span class="ko">6.3 콘텐츠 모니터링 및 집행</span>
<span class="es">6.3 Monitoreo y Aplicación de Contenido</span>
</h3>
<p class="en">The operator reserves the right, but has no obligation, to monitor, review, or remove any content generated through the service. The operator may, at its sole discretion and without prior notice: (a) delete content that violates these terms; (b) suspend or terminate the accounts of users who violate these terms; (c) report illegal content to relevant law enforcement authorities; (d) cooperate with law enforcement investigations. Violations of Section 6.2(a) will be reported to the appropriate authorities including NCMEC (National Center for Missing &amp; Exploited Children) where applicable.</p>
<p class="ja">運営者は、サービスを通じて生成されたコンテンツを監視、審査、または削除する権利を有しますが、義務は負いません。運営者は独自の判断により、事前通知なく以下の措置を講じることができます：(a) 本規約に違反するコンテンツの削除、(b) 本規約に違反するユーザーのアカウントの停止または終了、(c) 違法コンテンツの関係法執行機関への報告、(d) 法執行機関の捜査への協力。第6.2条(a)の違反については、該当する場合NCMEC（全米行方不明・被搾取児童センター）を含む関係当局に報告されます。</p>
<p class="zh">运营者保留监控、审查或删除通过本服务生成的任何内容的权利，但没有义务这样做。运营者可自行决定在不事先通知的情况下：(a) 删除违反本条款的内容；(b) 暂停或终止违反本条款的用户账户；(c) 向相关执法机构举报违法内容；(d) 配合执法机构的调查。违反第6.2条(a)的行为将向包括NCMEC（美国国家失踪和受剥削儿童中心）在内的相关当局报告。</p>
<p class="ko">운영자는 서비스를 통해 생성된 콘텐츠를 모니터링, 검토 또는 삭제할 권리가 있지만 의무는 없습니다. 운영자는 자체 판단에 따라 사전 통지 없이 다음 조치를 취할 수 있습니다: (a) 본 약관을 위반하는 콘텐츠 삭제, (b) 본 약관을 위반하는 사용자의 계정 정지 또는 해지, (c) 불법 콘텐츠의 관련 법 집행 기관에 신고, (d) 법 집행 기관 수사에 협력. 제6.2조(a) 위반 시 해당되는 경우 NCMEC(미국 실종·착취 아동센터)를 포함한 관련 당국에 보고됩니다.</p>
<p class="es">El operador se reserva el derecho, pero no tiene la obligación, de monitorear, revisar o eliminar cualquier contenido generado a través del servicio. El operador puede, a su exclusiva discreción y sin previo aviso: (a) eliminar contenido que viole estos términos; (b) suspender o cancelar las cuentas de usuarios que violen estos términos; (c) reportar contenido ilegal a las autoridades policiales pertinentes; (d) cooperar con investigaciones policiales. Las violaciones de la Sección 6.2(a) serán reportadas a las autoridades correspondientes, incluyendo NCMEC (Centro Nacional para Niños Desaparecidos y Explotados) cuando corresponda.</p>

<h3>
<span class="en">6.4 User Acknowledgment</span>
<span class="ja">6.4 ユーザーの承認</span>
<span class="zh">6.4 用户确认</span>
<span class="ko">6.4 사용자 확인</span>
<span class="es">6.4 Reconocimiento del Usuario</span>
</h3>
<p class="en">By using this service, you acknowledge and agree that: (a) the operator is not responsible for verifying whether your generated content infringes upon third-party rights; (b) any legal consequences arising from your generated content are your sole responsibility; (c) the operator may take action against your account if it reasonably believes you have violated these terms, without incurring any liability to you.</p>
<p class="ja">本サービスを利用することにより、ユーザーは以下を承認し同意するものとします：(a) 運営者は生成コンテンツが第三者の権利を侵害しているかどうかを確認する責任を負わないこと、(b) 生成コンテンツから生じるいかなる法的結果もユーザーの単独の責任であること、(c) 運営者がユーザーによる本規約違反があると合理的に判断した場合、ユーザーに対していかなる責任も負うことなくアカウントに対する措置を講じることができること。</p>
<p class="zh">使用本服务即表示您确认并同意：(a) 运营者不负责验证您生成的内容是否侵犯第三方权利；(b) 您生成的内容所产生的任何法律后果由您自行承担；(c) 如果运营者合理认为您违反了本条款，可以对您的账户采取措施，且不对您承担任何责任。</p>
<p class="ko">본 서비스를 이용함으로써 사용자는 다음 사항을 인정하고 동의합니다: (a) 운영자는 사용자의 생성 콘텐츠가 제3자의 권리를 침해하는지 확인할 책임이 없음, (b) 생성 콘텐츠에서 발생하는 모든 법적 결과는 전적으로 사용자의 책임임, (c) 운영자가 사용자의 본 약관 위반이 있다고 합리적으로 판단하는 경우, 사용자에 대한 어떠한 책임도 지지 않고 계정에 대한 조치를 취할 수 있음.</p>
<p class="es">Al usar este servicio, usted reconoce y acepta que: (a) el operador no es responsable de verificar si su contenido generado infringe los derechos de terceros; (b) cualquier consecuencia legal derivada de su contenido generado es su exclusiva responsabilidad; (c) el operador puede tomar medidas contra su cuenta si razonablemente cree que usted ha violado estos términos, sin incurrir en ninguna responsabilidad hacia usted.</p>

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
<p class="en">The operator shall not be liable for any direct, indirect, incidental, special, or consequential damages arising from the use of this service, including but not limited to: (a) damages arising from generated content, including intellectual property infringement claims by third parties; (b) damages resulting from the inability to use the service; (c) damages arising from unauthorized access to or alteration of your data; (d) any claims or damages arising from the use, distribution, or publication of content generated through this service. The operator's total cumulative liability, if any, shall not exceed the amount of credits purchased by the user in the twelve (12) months preceding the claim. This service is provided solely as a technical platform, and the operator bears no responsibility for how users utilize the generated content. Support is limited due to individual operation.</p>
<p class="ja">運営者は、本サービスの利用から生じる直接的、間接的、偶発的、特別、または結果的な損害について責任を負いません。これには以下が含まれますが、これらに限定されません：(a) 第三者による知的財産権侵害の請求を含む、生成コンテンツに起因する損害、(b) サービスを使用できないことによる損害、(c) データへの不正アクセスまたは改変から生じる損害、(d) 本サービスを通じて生成されたコンテンツの使用、配布、または公開に起因するあらゆる請求または損害。運営者の累計責任総額は、いかなる場合でも、請求に先立つ12か月間にユーザーが購入したクレジットの金額を超えないものとします。本サービスは技術的プラットフォームとしてのみ提供されており、運営者はユーザーが生成コンテンツをどのように利用するかについて一切の責任を負いません。個人運営のため、サポート対応範囲は限定的です。</p>
<p class="zh">运营者不对因使用本服务而产生的任何直接、间接、附带、特殊或后果性损害承担责任，包括但不限于：(a) 因生成内容引起的损害，包括第三方的知识产权侵权索赔；(b) 因无法使用服务造成的损害；(c) 因未经授权访问或修改您的数据而产生的损害；(d) 因使用、分发或发布通过本服务生成的内容而产生的任何索赔或损害。运营者的累计总责任（如有）不超过用户在索赔前十二(12)个月内购买的积分金额。本服务仅作为技术平台提供，运营者不对用户如何使用生成内容承担任何责任。由于个人运营，支持范围有限。</p>
<p class="ko">운영자는 본 서비스 이용으로 인해 발생하는 직접적, 간접적, 부수적, 특별 또는 결과적 손해에 대해 책임을 지지 않습니다. 여기에는 다음이 포함되나 이에 한정되지 않습니다: (a) 제3자의 지적 재산권 침해 청구를 포함하여 생성 콘텐츠로 인한 손해, (b) 서비스 이용 불가로 인한 손해, (c) 데이터에 대한 무단 접근 또는 변경으로 인한 손해, (d) 본 서비스를 통해 생성된 콘텐츠의 사용, 배포 또는 게시로 인한 모든 청구 또는 손해. 운영자의 누적 총 책임액은 청구 전 12개월간 사용자가 구매한 크레딧 금액을 초과하지 않습니다. 본 서비스는 기술 플랫폼으로만 제공되며, 운영자는 사용자가 생성 콘텐츠를 어떻게 활용하는지에 대해 일체의 책임을 지지 않습니다. 개인 운영으로 인해 지원 범위는 제한적입니다.</p>
<p class="es">El operador no será responsable de ningún daño directo, indirecto, incidental, especial o consecuente que surja del uso de este servicio, incluyendo pero no limitado a: (a) daños derivados del contenido generado, incluidas las reclamaciones de infracción de propiedad intelectual por parte de terceros; (b) daños resultantes de la imposibilidad de usar el servicio; (c) daños derivados del acceso no autorizado o la alteración de sus datos; (d) cualquier reclamación o daño derivado del uso, distribución o publicación de contenido generado a través de este servicio. La responsabilidad acumulativa total del operador, en su caso, no excederá el monto de créditos adquiridos por el usuario en los doce (12) meses anteriores a la reclamación. Este servicio se proporciona únicamente como una plataforma técnica, y el operador no asume ninguna responsabilidad sobre cómo los usuarios utilizan el contenido generado. El soporte es limitado debido a la operación individual.</p>

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
<p class="en">You agree to indemnify, defend, and hold harmless the operator from and against any and all claims, damages, losses, liabilities, costs, and expenses (including reasonable legal fees) arising from or related to: (a) your use of the service; (b) content you generate, download, share, or distribute using the service; (c) any claim that your generated content infringes upon the intellectual property rights, privacy rights, or other rights of any third party; (d) your violation of these terms or any applicable law; (e) any third-party claim resulting from your use of generated content for commercial or public purposes. This indemnification obligation shall survive the termination of your account and these terms.</p>
<p class="ja">ユーザーは、以下に起因するまたは関連するあらゆる請求、損害、損失、責任、費用、および経費（合理的な弁護士費用を含む）について、運営者を補償、防御、および免責することに同意するものとします：(a) ユーザーによるサービスの利用、(b) ユーザーがサービスを使用して生成、ダウンロード、共有、または配布したコンテンツ、(c) ユーザーの生成コンテンツが第三者の知的財産権、プライバシー権、またはその他の権利を侵害しているという請求、(d) ユーザーによる本規約または適用法令の違反、(e) ユーザーが生成コンテンツを商業的またはパブリックな目的で使用したことに起因する第三者からの請求。この補償義務は、アカウントの終了および本規約の終了後も存続するものとします。</p>
<p class="zh">您同意就以下原因或相关事项产生的任何及所有索赔、损害、损失、责任、成本和费用（包括合理的律师费）对运营者进行赔偿、辩护并使其免受损害：(a) 您对服务的使用；(b) 您使用服务生成、下载、共享或分发的内容；(c) 任何关于您生成的内容侵犯第三方知识产权、隐私权或其他权利的索赔；(d) 您违反本条款或任何适用法律；(e) 因您将生成内容用于商业或公共目的而导致的任何第三方索赔。此赔偿义务在您的账户终止和本条款终止后继续有效。</p>
<p class="ko">이용자는 다음에 기인하거나 관련된 모든 청구, 손해, 손실, 책임, 비용 및 경비(합리적인 변호사 비용 포함)에 대해 운영자를 보상, 방어하고 면책하는 데 동의합니다: (a) 이용자의 서비스 이용, (b) 이용자가 서비스를 사용하여 생성, 다운로드, 공유 또는 배포한 콘텐츠, (c) 이용자의 생성 콘텐츠가 제3자의 지적 재산권, 프라이버시권 또는 기타 권리를 침해한다는 청구, (d) 이용자의 본 약관 또는 관련 법률 위반, (e) 이용자가 생성 콘텐츠를 상업적 또는 공적 목적으로 사용하여 발생한 제3자의 청구. 이 보상 의무는 계정 해지 및 본 약관 종료 후에도 존속합니다.</p>
<p class="es">Usted acepta indemnizar, defender y eximir de responsabilidad al operador de y contra todas y cada una de las reclamaciones, daños, pérdidas, responsabilidades, costos y gastos (incluidos los honorarios legales razonables) que surjan de o estén relacionados con: (a) su uso del servicio; (b) el contenido que genere, descargue, comparta o distribuya utilizando el servicio; (c) cualquier reclamación de que su contenido generado infringe los derechos de propiedad intelectual, derechos de privacidad u otros derechos de cualquier tercero; (d) su violación de estos términos o cualquier ley aplicable; (e) cualquier reclamación de terceros resultante de su uso del contenido generado con fines comerciales o públicos. Esta obligación de indemnización sobrevivirá a la terminación de su cuenta y estos términos.</p>

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
