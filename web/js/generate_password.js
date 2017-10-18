$(document).ready(function(){

	var $btn = $('#generate');
	var $input = $btn.parent().prev('input');

	var min_length = 6;
	var extra_length = 2;
	var max_possible_nums = 2;
	var num_probability = 150;
	var hyphen_probability = 15;

	var vow = 'a.e.i.o.u.';
	vow += vow + vow;
	vow += 'y.oo.aa.ei.oe.ee.uu';
	var con = 'b.b.c.d.d.f.g.h.j.k.k.l.l.m.m.n.n.p.q.r.s.s.t.t.v.w.x.z.';
	con += con + con + con + con;
	con += 'tr.sc.bl.vl.cr.br.fr.th.dr.ch.ph.wr.vr.st.sp.sw.pr.sl.cl.';
	con += 'sch.nn.bb.ll.tt.ss.rr.nn.rt.ts.gl.ng.mn.zn.xn.sn';

	vow = vow.split('.');
	con = con.split('.');

	$btn.click(function(e){

		var length = min_length + Math.floor(Math.random() * extra_length);
		var vc = Math.floor(Math.random() * 2);
		var pw = '';
		var ran = 0;
		var num = 0;
		var i = 0;		
		var possible_nums = Math.floor(Math.random() * max_possible_nums);
		var hyphen_possible = false;

		for (i = 0; i < length; i++)
		{
			if (hyphen_possible)
			{
				ran = Math.floor(Math.random() * hyphen_probability);

				if (ran === 0)
				{
					pw += '-';
					hyphen_possible = false;
					continue;
				}
			}

			hyphen_possible = i < length - 2 ? true : false;

			if (possible_nums)
			{
				num = Math.floor(Math.random() * num_probability);
				
				if (num < 10)
				{
					pw += num;
					possible_nums--;
					continue;
				}
			}

			if (vc)
			{
				ran = Math.floor(Math.random() * con.length);
				pw += con[ran];
			}
			else
			{
				ran = Math.floor(Math.random() * vow.length);
				pw += vow[ran];
			}

			vc++;
			vc = vc > 1 ? 0 : 1;
		}

		$input.val(pw);

		e.preventDefault();
	});
});

