'use client';

import { useMemo, useState } from 'react';

const plans = [
  { name: 'Free', note: 'For a small campus getting started', price: '₱0', unit: 'forever', features: ['100 students', '10 faculty & staff', '1 administrator', 'ZenCraft-hosted address'], cta: 'Start for free' },
  { name: 'Starter', note: 'The foundation for growing campuses', price: '₱5,000', unit: 'per month', features: ['Up to 500 billable users', 'Up to 5 admins included', 'Custom campus domain', 'Core reports & exports'], cta: 'Choose Starter', featured: true },
  { name: 'Growth', note: 'For established institutions', price: '₱8', unit: 'per user above 500', features: ['501–1,000 billable users', 'Up to 5 admins included', 'Priority support', 'Automated backups'], cta: 'Talk to sales' },
  { name: 'Scale', note: 'For large campuses and school groups', price: '₱6.50', unit: 'per user above 1,000', features: ['1,001–2,000 billable users', 'Up to 5 admins included', 'Guided data migration', 'Launch support'], cta: 'Talk to sales' },
];

const freeSignupUrl = 'https://demo.zencraftwebservices.online/signup';

const benefits = [
  ['01', 'A single campus record', 'Students, faculty, classes, attendance, and campus data stay connected in one dependable source of truth.'],
  ['02', 'A guided implementation', 'Your secure campus workspace is created automatically, with practical tools for importing people and records.'],
  ['03', 'A platform built to grow', 'Begin with the essentials, add users as enrollment grows, and receive lower rates at higher tiers.'],
];

function peso(value: number) {
  return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(value);
}

export default function Home() {
  const [users, setUsers] = useState(750);
  const estimate = useMemo(() => {
    if (users <= 111) return { value: '₱0', note: 'You may qualify for the Free plan.' };
    if (users <= 500) return { value: '₱5,000', note: 'Starter includes up to 500 active users.' };
    if (users <= 1000) return { value: peso(5000 + (users - 500) * 8), note: 'Users above 500 receive the Growth rate.' };
    if (users <= 2000) return { value: peso(9000 + (users - 1000) * 6.5), note: 'Users above 1,000 receive the Scale rate.' };
    return { value: 'Custom', note: 'Let’s build a plan for your institution.' };
  }, [users]);

  return (
    <main>
      <nav className="nav shell" aria-label="Main navigation">
        <a className="brand productBrand" href="#top">
          <span className="brandSymbol" aria-hidden="true"><img src="/zencraft-logo.png" alt="" /></span>
          <span className="productName">ZenCraft <b>CSMS</b><small>Campus &amp; Student Management System</small></span>
        </a>
        <div className="navlinks"><a href="#features">Platform</a><a href="#pricing">Pricing</a><a href="#setup">Implementation</a></div>
        <a className="btn dark small" href="#contact">Book a demo <span>↗</span></a>
      </nav>

      <section className="hero shell" id="top">
        <div className="heroCopy">
          <span className="pill">✦ CAMPUS &amp; STUDENT MANAGEMENT SYSTEM</span>
          <h1>One campus.<br /><em>Clearly managed.</em></h1>
          <p>ZenCraft CSMS connects student records, faculty, attendance, classes, and campus operations in one calm, secure workspace built for Philippine schools.</p>
          <div className="actions"><a className="btn orange" href="#pricing">Explore plans <span>→</span></a><a className="watch" href="#setup"><i>▶</i> See implementation</a></div>
          <div className="proof brandProof"><span className="proofMark" aria-hidden="true"><img src="/zencraft-logo.png" alt="" /></span><p><b>Designed and supported by ZenCraft.</b><br />Local implementation. Human support. Clear pricing.</p></div>
        </div>

        <div className="preview">
          <div className="ring one" /><div className="ring two" />
          <div className="app">
            <header><a className="brand mini"><span className="brandSymbol"><img src="/zencraft-logo.png" alt="" /></span>CSMS</a><div className="search">⌕ &nbsp; Search students, staff, records...</div><span className="user">MC</span></header>
            <div className="appBody">
              <aside><b>⌂ &nbsp; Overview</b><span>♙ &nbsp; Students</span><span>◇ &nbsp; Staff</span><span>▦ &nbsp; Classes</span><span>◷ &nbsp; Attendance</span><div><strong>Need help?</strong><small>We’re here for you.</small><button>Contact us</button></div></aside>
              <section className="dash">
                <div className="hello"><div><small>MONDAY, AUGUST 24</small><h3>Good morning, Maria!</h3><p>Here’s what’s happening across your campus today.</p></div><button>+ Add student</button></div>
                <div className="stats"><div><i className="mint">♙</i><span><small>Total students</small><strong>486</strong><em>↑ 12 this month</em></span></div><div><i className="peach">◇</i><span><small>Teachers & staff</small><strong>38</strong><em>96% active</em></span></div><div><i className="blue">✓</i><span><small>Today’s attendance</small><strong>94.8%</strong><em>↑ 2.1% this week</em></span></div></div>
                <div className="lower"><div className="chart"><b>Weekly attendance</b><div className="bars">{[58,74,66,91,83].map((h, i) => <i key={i} style={{height: `${h}%`}} />)}</div><small><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span></small></div><div className="activity"><b>Recent activity</b><p><i>✓</i><span><strong>Enrollment approved</strong><small>Grade 8 • 4 mins ago</small></span></p><p><i>↗</i><span><strong>Report exported</strong><small>Attendance • 1 hour ago</small></span></p><p><i>+</i><span><strong>New staff added</strong><small>Faculty • Yesterday</small></span></p></div></div>
              </section>
            </div>
          </div>
          <div className="float setup"><i>✓</i><span><b>Campus configured</b><small>Your workspace is ready</small></span></div>
          <div className="float attend"><i>94%</i><span><b>Great attendance</b><small>Across all year levels</small></span></div>
        </div>
      </section>

      <section className="trust"><div className="shell"><p>Campus operations, connected.<br /><b>Management, simplified.</b></p><div><span>✓ Automated setup</span><span>✓ Secure by design</span><span>✓ Local support</span><span>✓ Transparent tiers</span></div></div></section>

      <section className="section shell" id="features">
        <div className="sectionHead split"><div><span>THE ZENCRAFT CSMS PLATFORM</span><h2>Every campus function.<br /><em>One clear system.</em></h2></div><p>Designed for the people who keep an institution moving—from the registrar and administrator to faculty and staff.</p></div>
        <div className="benefits">{benefits.map(([n, title, copy]) => <article key={n}><small>{n}</small><div className={`art a${n}`}><i /><b /><span /></div><h3>{title}</h3><p>{copy}</p></article>)}</div>
      </section>

      <section className="pricing" id="pricing"><div className="shell">
        <div className="sectionHead center"><span>SIMPLE, TRANSPARENT PRICING</span><h2>Start small. <em>Scale fairly.</em></h2><p>Students, faculty, and staff count toward your tier. Up to five administrative accounts are included on paid plans.</p></div>
        <div className="plans">{plans.map(plan => <article className={plan.featured ? 'featured' : ''} key={plan.name}>{plan.featured && <div className="popular">MOST POPULAR</div>}<h3>{plan.name}</h3><p>{plan.note}</p><strong>{plan.price}</strong><small>{plan.unit}</small><hr /><ul>{plan.features.map(item => <li key={item}><i>✓</i>{item}</li>)}</ul><a className={`btn ${plan.featured ? 'orange' : 'outline'}`} href={plan.name === 'Free' ? freeSignupUrl : '#contact'}>{plan.cta} <span>→</span></a></article>)}</div>
        <div className="enterprise"><div><span>MORE THAN 2,000 USERS</span><h3>Managing a larger institution or campus group?</h3><p>We’ll create a plan around your campuses, migration needs, integrations, and support requirements.</p></div><a className="btn white" href="#contact">Build a custom plan →</a></div>
        <div className="estimator"><div><span>QUICK ESTIMATE</span><h3>Estimate your monthly CSMS subscription.</h3><p>Move the slider to preview graduated pricing. A one-time implementation fee is included in your initial quotation.</p></div><div className="tool"><label>Billable users <output>{users.toLocaleString()}</output></label><input aria-label="Billable users" type="range" min="100" max="2500" step="10" value={users} onChange={e => setUsers(Number(e.target.value))} style={{'--progress': `${((users - 100) / 2400) * 100}%`} as React.CSSProperties} /><div className="labels"><span>100</span><span>500</span><span>1,000</span><span>2,000+</span></div><div className="result"><span><small>ESTIMATED MONTHLY</small><b>{estimate.value}</b></span><p>{estimate.note}</p></div></div></div>
      </div></section>

      <section className="section shell setupSteps" id="setup"><div className="sectionHead center"><span>FROM SIGN-UP TO CAMPUS-READY</span><h2>Implemented in <em>three clear steps.</em></h2></div><div className="steps"><article><b>1</b><small>CREATE</small><h3>Define your campus</h3><p>Choose your plan and provide the essentials. Your secure ZenCraft CSMS workspace is created automatically.</p></article><article><b>2</b><small>CONFIGURE</small><h3>Bring your campus records</h3><p>Import students, faculty, and staff, then configure your academic year, classes, and access roles.</p></article><article><b>3</b><small>LAUNCH</small><h3>Review and go live</h3><p>Validate your setup, invite your team, and begin managing your campus from one connected system.</p></article></div><div className="help">✦ <p><b>Implementation is part of the plan.</b> Your initial quotation includes a one-time setup fee covering configuration, onboarding, and launch support.</p></div></section>

      <section className="cta" id="contact"><div className="ctaRing r1" /><div className="ctaRing r2" /><div className="shell"><span className="ctaLogo" aria-hidden="true"><img src="/zencraft-logo.png" alt="" /></span><small>A CLEARER WAY TO RUN YOUR CAMPUS</small><h2>Bring your campus<br />into <em>one system.</em></h2><p>See how ZenCraft CSMS can support your institution today—and scale with you tomorrow.</p><div><a className="btn orange" href="mailto:hello@zencraft.ph?subject=ZenCraft%20CSMS%20Demo">Book your free demo →</a><a className="btn ghost" href="#pricing">View pricing</a></div></div></section>
      <footer><div className="shell"><div className="footerBrand"><img src="/zencraft-logo.png" alt="ZenCraft Web Services" /><span>Creator of ZenCraft CSMS</span></div><p>Campus management, clearly connected.</p><nav><a href="#features">Platform</a><a href="#pricing">Pricing</a><a href="mailto:hello@zencraft.ph">Contact</a></nav><small>© 2026 ZenCraft Web Services.</small></div></footer>
    </main>
  );
}
